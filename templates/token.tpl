<div x-data='token_handler({$formData|@json_encode})' class="max-w-screen-xl  2xl:mx-auto flex">
    <div class="flex p-4 w-4/6 bg-white rounded-lg shadow-lg border mx-auto">
        <div class="w-full mx-auto">
            <form class="flex flex-col gap-4" x-ref="form">
                <div class="text-lg font-bold">
                    Token Authorization
                    <small class="block font-normal text-gray-500">
                        Enter the token you bought at <a href="https://openjournaltheme.com"
                            class="text-blue-500">openjournaltheme.com</a></small>
                </div>
                <div class="font-medium bg-success-100 rounded-lg py-5 px-6 mb-4 text-base text-success-700 mb-3"
                    role="alert" x-show="quota_added">
                    Congratulations, <span x-text="quota_added"></span> quota has been successfully added !
                    <small class="block text-small font-normal">
                        Current Quota: <span x-text="current_quota"></span>
                    </small>
                </div>
                <div class="form-group">
                    <label for="editor_name">Token <span class="text-danger-700">*</span></label>
                    <input autocomplete="off" x-model="formData.token" class="w-full" placeholder="Enter token"
                        type="text" required>
                </div>
                <div class="mt-2 flex gap-2 items-center">
                    <button type="submit" @click.prevent="submit()"
                        {* @click="nextClicked=true, setTimeout(()=>register('{$journal_id|base64_encode}','{$journal_name|base64_encode}','{$online_issn|base64_encode}','{$print_issn|base64_encode}','{$root_url|base64_encode}'),300)" *}
                        :class="{ 'hover:cursor-progress': activationButton.loading }"
                        :disabled="activationButton.loading" class="ml-auto btn-primary"
                        x-html="activationButton.loading ? activationButton.textLoading : activationButton.text">
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>