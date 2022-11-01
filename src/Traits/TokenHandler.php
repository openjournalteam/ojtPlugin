<?php

namespace Openjournalteam\OjtPlugin\Traits;

use Openjournalteam\OjtPlugin\Classes\OJTService;
use Openjournalteam\OjtPlugin\Exceptions\InvalidCallbackSubmitTokenValue;

trait TokenHandler
{
    /** get plugin instance */
    abstract protected function getPlugin();

    /** get base journal url */
    protected function getBaseUrl($request)
    {
        $baseUrl = $this->baseUrl ?? false;
        if (!$baseUrl) {
            $baseUrl = $this->request->getDispatcher()->url($this->request, ROUTE_PAGE, $this->request->getContext());
        }
        return $baseUrl;
    }

    /**ge ojt plugin instance */
    abstract protected function getOjtPlugin();

    /** Token, Journal URL, Product */
    protected function requiredPayload()
    {
        $plugin = $this->getPlugin();
        return [
            'product' => $plugin->getName(),
            'token' => $plugin->getSetting($plugin->getCurrentContextId(), 'token'),
            'journal_url' => $this->getBaseUrl(),
        ];
    }

    protected function setPayload($payload)
    {
        return array_merge($this->requiredPayload(), $payload);
    }

    public function history_token()
    {
    }

    public function token_activation($request)
    {
        ajaxOrError();
        $pluginFullUrl = $this->getPluginFullUrl();
        $ojtPluginFullUrl = $this->getOjtPlugin()->getPluginFullUrl('', false);

        $formData = [
            'token' => $this->getPlugin()->getSetting(
                $this->getPlugin()->getCurrentContextId(),
                'token'
            ),
        ];

        $templateMgr = \TemplateManager::getManager($request);

        $templateMgr->assign('formData', $formData);
        $templateMgr->assign('base_url',   $pluginFullUrl);

        $json['css']  = [$pluginFullUrl . '/assets/css/app.css'];
        $json['js']   = [$ojtPluginFullUrl . '/assets/js/token.js'];

        $json['html'] = $templateMgr->fetch($this->getOjtPlugin()->getTemplateResource('token.tpl'));
        return showJson($json);
    }

    /** Token activation */
    public function submit_token($args, $request)
    {
        $token = $_POST['token'] ?? false;

        if (!$token) {
            return showJson([
                'error' => true,
                'msg' => "Token is required"
            ]);
        }

        $plugin = $this->getPlugin();

        $plugin->updateSetting(
            $plugin->getCurrentContextId(),
            'token',
            $token
        );

        $response = (new OJTService())->request(
            '/api/v2/subscription/register',
            $this->requiredPayload(),
        );

        if ($response['error']) {
            return showJson($response);
        }

        $plugin->updateSetting(
            $plugin->getCurrentContextId(),
            'quota',
            $response['quota']
        );

        $plugin->updateSetting(
            $plugin->getCurrentContextId(),
            'current_quota',
            $plugin->getSetting(
                $plugin->getCurrentContextId(),
                'quota'
            )
        );

        if (method_exists($this, 'callback_submit_token')) {
            ob_start();
            $callbackResult = call_user_func([$this, 'callback_submit_token'], $args, $request);
            ob_end_clean();
            if (!filter_var($callbackResult, FILTER_VALIDATE_URL)) {
                throw new InvalidCallbackSubmitTokenValue("Callback should return string ( URL to Page if you want to create a redirect ) or nothing");
            }
        }

        return showJson($response);
    }
}
