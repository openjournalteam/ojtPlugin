{* <div
  x-data="{ tab: 1,activeTabClass: 'ojt-border-l ojt-border-t ojt-border-r ojt-rounded-t ojt-text-primary-700',inactiveTabClass: 'ojt-text-primary-500 hover:ojt-text-primary-800' }"
  id="ojt-setting"
  class="ojt-my-4 ojt-border ojt-w-auto ojt-max-w-screen-xl 2xl:ojt-mx-auto ojt-p-4 ojt-bg-white ojt-shadow-lg dark:ojt-bg-darker ojt-mx-4 ojt-transition-height ojt-duration-1000 ojt-ease-in-out">
  <div class="lg:ojt-flex lg:ojt-items-center ojt-mb-4">
    <div class="ojt-flex ojt-items-center">
      <span class="ojt-text-xl ojt-font-bold">Dashboard</span>
    </div>
    <div class="ojt-ml-auto ojt-flex ojt-items-center">

    </div>
  </div>
  <ul class="ojt-flex ojt-border-b">
    <li @click="tab = 1" :class="{ 'ojt--mb-px': tab === 1 }" class="ojt--mb-px ojt-ml-3 ojt-mr-1">
      <a :class="tab === 1 ? activeTabClass : inactiveTabClass"
        class="ojt-bg-white ojt-inline-block ojt-py-2 ojt-px-4 ojt-font-semibold dark:ojt-bg-dark dark:ojt-text-light"
        href="#">
        Installed Plugins
      </a>
    </li>
    <li @click="tab = 2" :class="{ 'ojt--mb-px': tab === 2 }"
      @mouseenter.once="initiateAjaxContent($('.plugingallery'));" class="plugingallerytab ojt-mr-1">
      <a :class="tab === 2 ? activeTabClass : inactiveTabClass"
        class="ojt-bg-white ojt-inline-block ojt-py-2 ojt-px-4 ojt-font-semibold dark:ojt-bg-dark dark:ojt-text-light"
        href="#">
        Plugin Gallery
      </a>
    </li>
  </ul>
  <div x-show="tab === 1" x-transition:enter="ojt-transition ojt-ease-out ojt-duration-100"
    x-transition:enter-start="ojt-transform ojt-opacity-0 ojt-scale-95"
    x-transition:enter-end="ojt-transform ojt-opacity-100 ojt-scale-100" class="ajax_load plugininstalled"
    page="ojt/pluginInstalled">

  </div>
  <div x-show="tab === 2" x-transition:enter="ojt-transition ojt-ease-out ojt-duration-100"
    x-transition:enter-start="ojt-transform ojt-opacity-0 ojt-scale-95"
    x-transition:enter-end="ojt-transform ojt-opacity-100 ojt-scale-100" class="ajax_load plugingallery"
    page="ojt/pluginGallery">

  </div>
</div>

<script type="text/javascript">
  initiateAjaxContent($('.plugininstalled'));
</script> *}