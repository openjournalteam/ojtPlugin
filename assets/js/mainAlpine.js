// akses alpine x-data melalui id
function alpineComponent(id) {
  return document.getElementById(id).__x.$data;
}

// Background Jobs is loaded with the main Alpine bundle because the settings
// panel itself is injected into the page after Alpine has already started.
function backgroundJobsMock() {
  return {
    view: "queue",
    filter: "all",
    query: "",
    dateRange: "all",
    dateOpen: false,
    selectedJob: null,
    detailsOpen: false,
    page: 1,
    pageSize: 10,
    filterOptions: [
      { key: "all", label: "All" },
      { key: "active", label: "Active" },
      { key: "done", label: "Completed" },
      { key: "failed", label: "Failed" },
    ],
    dateOptions: [
      { key: "all", label: "All time" },
      { key: "today", label: "Today" },
      { key: "7d", label: "Last 7 days" },
      { key: "30d", label: "Last 30 days" },
      { key: "month", label: "This month" },
    ],
    jobs: [
      { id: 1, name: "Reindex article metadata", status: "processing", progress: 64, paused: false, meta: "64%", started: "14:32:05", finished: "", duration: "" },
      { id: 2, name: "Generate DOI batch (Crossref)", status: "processing", progress: 31, paused: false, meta: "31%", started: "14:33:40", finished: "", duration: "" },
      { id: 3, name: "Send digest notifications", status: "paused", progress: 12, paused: true, meta: "Paused", started: "14:34:12", finished: "", duration: "" },
      { id: 4, name: "Rebuild search index", status: "queued", progress: 0, paused: false, meta: "Waiting...", started: "—", finished: "", duration: "" },
      { id: 5, name: "Export OAI records", status: "queued", progress: 0, paused: false, meta: "Waiting...", started: "—", finished: "", duration: "" },
      { id: 6, name: "Optimize cover images", status: "done", progress: 100, paused: false, meta: "Completed", started: "14:05:11", finished: "14:07:48", duration: "2m 37s" },
      { id: 7, name: "Nightly database backup", status: "done", progress: 100, paused: false, meta: "Completed", started: "02:00:00", finished: "02:18:33", duration: "18m 33s" },
      { id: 8, name: "Sync ORCID profiles", status: "done", progress: 100, paused: false, meta: "Completed", started: "13:50:20", finished: "13:51:02", duration: "42s" },
      { id: 9, name: "Import submissions (CSV)", status: "failed", progress: 47, paused: false, meta: "Failed at 47%", started: "14:20:00", finished: "14:22:14", duration: "2m 14s" },
      { id: 10, name: "PDF galley conversion", status: "failed", progress: 8, paused: false, meta: "Failed at 8%", started: "14:28:31", finished: "14:28:49", duration: "18s" },
      { id: 11, name: "Purge expired sessions", status: "done", progress: 100, paused: false, meta: "Completed", started: "03:00:00", finished: "03:00:42", duration: "42s" },
      { id: 12, name: "Recompute citation counts", status: "done", progress: 100, paused: false, meta: "Completed", started: "01:15:00", finished: "01:41:20", duration: "26m 20s" },
      { id: 13, name: "Warm PDF thumbnail cache", status: "failed", progress: 22, paused: false, meta: "Failed at 22%", started: "12:04:00", finished: "12:05:31", duration: "1m 31s" },
      { id: 14, name: "Archive old issues", status: "done", progress: 100, paused: false, meta: "Completed", started: "22:00:00", finished: "22:47:10", duration: "47m 10s" },
    ],
    schedules: [
      { id: 1, name: "Nightly database backup", cron: "0 2 * * *", human: "Daily at 02:00", enabled: true, next: "Tomorrow 02:00", last: "Today 02:00" },
      { id: 2, name: "Rebuild search index", cron: "0 */6 * * *", human: "Every 6 hours", enabled: true, next: "Today 18:00", last: "Today 12:00" },
      { id: 3, name: "Send digest notifications", cron: "0 6 * * 1", human: "Mondays at 06:00", enabled: true, next: "Mon 06:00", last: "Last Mon 06:00" },
      { id: 4, name: "Purge expired sessions", cron: "*/30 * * * *", human: "Every 30 minutes", enabled: false, next: "Paused", last: "Today 03:00" },
      { id: 5, name: "Recompute citation counts", cron: "0 1 * * 0", human: "Sundays at 01:00", enabled: true, next: "Sun 01:00", last: "Last Sun 01:00" },
    ],
    activeCount() {
      return this.jobs.filter((job) => ["processing", "paused", "queued"].includes(job.status)).length;
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.filteredJobs().length / this.pageSize));
    },
    visiblePages() {
      const total = this.totalPages();
      const current = Math.min(this.page, total);
      if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
      if (current <= 4) return [1, 2, 3, 4, "ellipsis-end", total];
      if (current >= total - 3) return [1, "ellipsis-start", total - 3, total - 2, total - 1, total];
      return [1, "ellipsis-start", current - 1, current, current + 1, "ellipsis-end", total];
    },
    pageInfo() {
      const total = this.filteredJobs().length;
      if (!total) return "Showing 0 of 0";
      const start = (this.page - 1) * this.pageSize + 1;
      const end = Math.min(this.page * this.pageSize, total);
      return "Showing " + start + "–" + end + " of " + total;
    },
    filteredJobs() {
      const query = this.query.trim().toLowerCase();
      return this.jobs.filter((job) => {
        const matchesQuery = !query || job.name.toLowerCase().includes(query);
        let matchesFilter = true;
        if (this.filter === "active") matchesFilter = ["processing", "paused", "queued"].includes(job.status);
        if (this.filter === "done") matchesFilter = job.status === "done";
        if (this.filter === "failed") matchesFilter = job.status === "failed";
        return matchesQuery && matchesFilter;
      });
    },
    pagedJobs() {
      const start = (this.page - 1) * this.pageSize;
      return this.filteredJobs().slice(start, start + this.pageSize);
    },
    countFor(filter) {
      if (filter === "all") return this.jobs.length;
      if (filter === "active") return this.jobs.filter((job) => ["processing", "paused", "queued"].includes(job.status)).length;
      return this.jobs.filter((job) => job.status === filter).length;
    },
    setFilter(filter) {
      this.filter = filter;
      this.page = 1;
    },
    previousPage() {
      this.page = Math.max(1, this.page - 1);
    },
    nextPage() {
      this.page = Math.min(this.totalPages(), this.page + 1);
    },
    confirmJobAction(action, id) {
      const job = this.jobs.find((item) => item.id === id);
      if (!job) return;

      const copy = {
        pause: { title: "Pause this job?", text: "The job will remain in the queue until resumed.", confirm: "Pause job", color: "#7c3aed", icon: "question" },
        resume: { title: "Resume this job?", text: "The job will continue processing.", confirm: "Resume job", color: "#7c3aed", icon: "question" },
        stop: { title: "Stop this job?", text: "This job will be stopped and marked as failed.", confirm: "Stop job", color: "#ef4444", icon: "warning" },
        retry: { title: "Retry this job?", text: "A new attempt will be added to the queue.", confirm: "Retry job", color: "#7c3aed", icon: "question" },
      }[action];
      if (!copy) return;
      if (typeof Swal === "undefined") {
        this[action + "Job"](id);
        return;
      }

      Swal.fire({
        title: copy.title,
        text: job.name + ". " + copy.text,
        icon: copy.icon,
        showCancelButton: true,
        confirmButtonText: copy.confirm,
        cancelButtonText: "Cancel",
        confirmButtonColor: copy.color,
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) this[action + "Job"](id);
      });
    },
    confirmStopAllJobs() {
      if (typeof Swal === "undefined") {
        this.stopAllJobs();
        return;
      }

      Swal.fire({
        title: "Stop all active jobs?",
        text: "All processing, paused, and queued jobs will be stopped.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Stop all jobs",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#ef4444",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) this.stopAllJobs();
      });
    },
    updateJob(id, changes) {
      const job = this.jobs.find((item) => item.id === id);
      if (job) Object.assign(job, changes);
    },
    pauseJob(id) {
      this.updateJob(id, { status: "paused", paused: true, meta: "Paused" });
    },
    resumeJob(id) {
      const job = this.jobs.find((item) => item.id === id);
      if (job) this.updateJob(id, { status: "processing", paused: false, meta: job.progress + "%" });
    },
    stopJob(id) {
      this.updateJob(id, { status: "failed", paused: false, meta: "Stopped" });
    },
    retryJob(id) {
      this.updateJob(id, { status: "processing", paused: false, progress: 0, meta: "0%" });
    },
    stopAllJobs() {
      this.jobs.filter((job) => ["processing", "paused", "queued"].includes(job.status)).forEach((job) => {
        Object.assign(job, { status: "failed", paused: false, meta: "Stopped" });
      });
    },
    statusLabel(status) {
      return { processing: "Processing", paused: "Paused", queued: "Queued", done: "Done", failed: "Failed" }[status];
    },
    iconClasses(status) {
      return { processing: "ojt-bg-primary-50 ojt-text-primary-600", paused: "ojt-bg-primary-50 ojt-text-primary-600", queued: "ojt-bg-gray-100 ojt-text-gray-500", done: "ojt-bg-primary-50 ojt-text-primary-600", failed: "ojt-bg-primary-50 ojt-text-primary-600" }[status];
    },
    statusClasses(status) {
      return { processing: "ojt-bg-primary-50 ojt-text-primary-700", paused: "ojt-bg-warning-50 ojt-text-warning-700", queued: "ojt-bg-gray-100 ojt-text-gray-600", done: "ojt-bg-success-50 ojt-text-success-700", failed: "ojt-bg-danger-50 ojt-text-danger-700" }[status];
    },
    statusDotClasses(status) {
      return { processing: "ojt-bg-primary-500", paused: "ojt-bg-warning-500", queued: "ojt-bg-gray-500", done: "ojt-bg-success-500", failed: "ojt-bg-danger-500" }[status];
    },
    progressClasses(status) {
      return { processing: "ojt-bg-primary-600 ojt-job-progress-processing", paused: "ojt-bg-primary-600", queued: "ojt-bg-gray-300", done: "ojt-bg-primary-600", failed: "ojt-bg-primary-600" }[status];
    },
  };
}

/**
 * Background Jobs state connected to the OJT queue endpoints.
 * The settings markup is injected after Alpine starts, so this factory is
 * intentionally global and is reused by the inline settings template.
 */
function createBackgroundJobs(config = {}) {
  return {
    view: "queue",
    filter: "all",
    query: "",
    dateRange: "all",
    dateOpen: false,
    selectedJob: null,
    detailsOpen: false,
    page: 1,
    pageSize: 10,
    csrfToken: config.csrfToken || "",
    jobsEnabled: config.background_jobs_enabled !== false,
    loading: false,
    error: "",
    requestInFlight: false,
    pollTimer: null,
    counts: { all: 0, active: 0, done: 0, failed: 0 },
    pagination: { page: 1, pageSize: 10, total: 0, totalPages: 1 },
    filterOptions: [
      { key: "all", label: "All" },
      { key: "active", label: "Active" },
      { key: "done", label: "Completed" },
      { key: "failed", label: "Failed" },
    ],
    dateOptions: [
      { key: "all", label: "All time" },
      { key: "today", label: "Today" },
      { key: "7d", label: "Last 7 days" },
      { key: "30d", label: "Last 30 days" },
      { key: "month", label: "This month" },
    ],
    jobs: [],
    schedules: [],
    init() {
      this.loadJobs();
      this.pollTimer = window.setInterval(() => {
        if (this.view === "queue" && this.counts.active > 0) {
          this.loadJobs(true);
        }
      }, 4000);
    },
    async loadJobs(silent = false) {
      if (this.requestInFlight) return;
      this.requestInFlight = true;
      if (!silent) this.loading = true;
      this.error = "";

      try {
        const params = new URLSearchParams({
          page: String(this.page),
          pageSize: String(this.pageSize),
          filter: this.filter,
          query: this.query,
          dateRange: this.dateRange,
          queue: "default",
        });
        const response = await fetch(currentUrl + "getJobs?" + params.toString(), {
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });
        const payload = await response.json();
        if (!response.ok || payload.error) {
          throw new Error(payload.msg || "Unable to load background jobs.");
        }

        const data = payload.data || {};
        this.jobs = (data.jobs || []).map((job) => ({
          ...job,
          queuedAt: job.queuedAt || "",
          started: job.started || "—",
          pausedAt: job.pausedAt || "",
          finished: job.finished || "",
          stopped: job.stopped || "",
          duration: job.duration || "",
          details: job.details || {},
          paused: job.status === "paused",
        }));
        this.counts = data.counts || { all: 0, active: 0, done: 0, failed: 0 };
        this.pagination = data.pagination || { page: this.page, pageSize: this.pageSize, total: 0, totalPages: 1 };
        this.page = this.pagination.page || this.page;
        if (typeof payload.backgroundJobsEnabled !== "undefined") {
          this.jobsEnabled = payload.backgroundJobsEnabled !== false;
        }
      } catch (error) {
        this.error = error.message || "Unable to load background jobs.";
        if (!silent && typeof Toast !== "undefined") {
          Toast.fire({ icon: "error", title: this.error });
        }
      } finally {
        this.requestInFlight = false;
        this.loading = false;
      }
    },
    activeCount() {
      return this.counts.active || 0;
    },
      totalPages() {
        return Math.max(1, this.pagination.totalPages || 1);
      },
      visiblePages() {
        const total = this.totalPages();
        const current = Math.min(this.page, total);
        if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
        if (current <= 4) return [1, 2, 3, 4, "ellipsis-end", total];
        if (current >= total - 3) return [1, "ellipsis-start", total - 3, total - 2, total - 1, total];
        return [1, "ellipsis-start", current - 1, current, current + 1, "ellipsis-end", total];
      },
    pageInfo() {
      const total = this.pagination.total || 0;
      if (!total) return "Showing 0 of 0";
      const start = ((this.page - 1) * this.pageSize) + 1;
      const end = Math.min(this.page * this.pageSize, total);
      return "Showing " + start + "–" + end + " of " + total;
    },
    filteredJobs() {
      return this.jobs;
    },
    pagedJobs() {
      return this.jobs;
    },
    countFor(filter) {
      return this.counts[filter] || 0;
    },
    setFilter(filter) {
      this.filter = filter;
      this.page = 1;
      this.loadJobs();
    },
    selectedDateLabel() {
      const option = this.dateOptions.find((item) => item.key === this.dateRange);
      return option ? option.label : "All time";
    },
    setDateRange(range) {
      this.dateRange = range;
      this.dateOpen = false;
      this.page = 1;
      this.loadJobs();
    },
    openJobDetails(job) {
      this.selectedJob = job;
      this.detailsOpen = true;
    },
    closeJobDetails() {
      this.detailsOpen = false;
    },
    formatDetails(details) {
      try {
        return JSON.stringify(details || {}, null, 2);
      } catch (error) {
        return "Unable to display job details.";
      }
    },
    previousPage() {
      if (this.page > 1) {
        this.page -= 1;
        this.loadJobs();
      }
    },
    nextPage() {
      if (this.page < this.totalPages()) {
        this.page += 1;
        this.loadJobs();
      }
    },
    async sendAction(action, id = null, fields = {}) {
      const formData = new FormData();
      formData.append("action", action);
      formData.append("csrfToken", this.csrfToken);
      if (id !== null) formData.append("jobId", String(id));
      Object.entries(fields).forEach(([key, value]) => formData.append(key, String(value)));

      try {
        const response = await fetch(currentUrl + "jobAction", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });
        const payload = await response.json();
        if (!response.ok || payload.error) {
          throw new Error(payload.msg || "Unable to apply the job action.");
        }
        if (typeof Toast !== "undefined") {
          Toast.fire({ icon: "success", title: payload.msg || "Job action completed." });
        }
        await this.loadJobs();
        return true;
      } catch (error) {
        if (typeof Toast !== "undefined") {
          Toast.fire({ icon: "error", title: error.message || "Unable to apply the job action." });
        }
        return false;
      }
    },
    async setJobsEnabled(enabled) {
      const previous = this.jobsEnabled;
      this.jobsEnabled = enabled;
      const success = await this.sendAction("toggle_enabled", null, { enabled: enabled ? "1" : "0" });
      if (!success) this.jobsEnabled = previous;
    },
    confirmToggleJobs() {
      const enabled = !this.jobsEnabled;
      const copy = enabled
        ? { title: "Enable background jobs?", text: "New jobs can be dispatched and the worker can process queued jobs.", confirm: "Enable jobs", color: "#7c3aed" }
        : { title: "Disable background jobs?", text: "Queued jobs will remain safe in the queue, but new jobs will not be dispatched or processed.", confirm: "Disable jobs", color: "#6b7280" };

      if (typeof Swal === "undefined") return this.setJobsEnabled(enabled);
      Swal.fire({
        title: copy.title,
        text: copy.text,
        icon: enabled ? "question" : "warning",
        showCancelButton: true,
        confirmButtonText: copy.confirm,
        cancelButtonText: "Cancel",
        confirmButtonColor: copy.color,
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) this.setJobsEnabled(enabled);
      });
    },
    confirmJobAction(action, id) {
      const job = this.jobs.find((item) => item.id === id);
      if (!job) return;
      const copy = {
        pause: { title: "Pause this job?", text: "The job will remain in the queue until resumed.", confirm: "Pause job", color: "#7c3aed", icon: "question" },
        resume: { title: "Resume this job?", text: "The job will continue processing.", confirm: "Resume job", color: "#7c3aed", icon: "question" },
        stop: { title: "Stop this job?", text: "This job will be stopped and marked as failed.", confirm: "Stop job", color: "#ef4444", icon: "warning" },
        retry: { title: "Retry this job?", text: "A new attempt will be added to the queue.", confirm: "Retry job", color: "#7c3aed", icon: "question" },
      }[action];
      if (!copy) return;
      if (typeof Swal === "undefined") {
        return this.sendAction(action, id);
      }
      Swal.fire({
        title: copy.title,
        text: job.name + ". " + copy.text,
        icon: copy.icon,
        showCancelButton: true,
        confirmButtonText: copy.confirm,
        cancelButtonText: "Cancel",
        confirmButtonColor: copy.color,
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) this.sendAction(action, id);
      });
    },
    confirmStopAllJobs() {
      if (typeof Swal === "undefined") return this.stopAllJobs();
      Swal.fire({
        title: "Stop all active jobs?",
        text: "All processing, paused, and queued jobs will be stopped.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Stop all jobs",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#ef4444",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) this.stopAllJobs();
      });
    },
    pauseJob(id) { return this.sendAction("pause", id); },
    resumeJob(id) { return this.sendAction("resume", id); },
    stopJob(id) { return this.sendAction("stop", id); },
    retryJob(id) { return this.sendAction("retry", id); },
    stopAllJobs() { return this.sendAction("stop_all"); },
    statusLabel(status) {
      return { processing: "Processing", paused: "Paused", queued: "Queued", done: "Done", failed: "Failed" }[status] || status;
    },
    iconClasses(status) {
      return { processing: "ojt-bg-primary-50 ojt-text-primary-600", paused: "ojt-bg-primary-50 ojt-text-primary-600", queued: "ojt-bg-gray-100 ojt-text-gray-500", done: "ojt-bg-primary-50 ojt-text-primary-600", failed: "ojt-bg-primary-50 ojt-text-primary-600" }[status];
    },
    statusClasses(status) {
      return { processing: "ojt-bg-primary-50 ojt-text-primary-700", paused: "ojt-bg-warning-50 ojt-text-warning-700", queued: "ojt-bg-gray-100 ojt-text-gray-600", done: "ojt-bg-success-50 ojt-text-success-700", failed: "ojt-bg-danger-50 ojt-text-danger-700" }[status];
    },
    statusDotClasses(status) {
      return { processing: "ojt-bg-primary-500", paused: "ojt-bg-warning-500", queued: "ojt-bg-gray-500", done: "ojt-bg-success-500", failed: "ojt-bg-danger-500" }[status];
    },
    progressClasses(status) {
      return { processing: "ojt-bg-primary-600 ojt-job-progress-processing", paused: "ojt-bg-primary-600", queued: "ojt-bg-gray-300", done: "ojt-bg-primary-600", failed: "ojt-bg-primary-600" }[status];
    },
  };
}

// Alpine evaluates settings markup after this bundle has loaded.
function backgroundJobs(config = {}) {
  return createBackgroundJobs(config);
}

const utama = () => {
  const getTheme = () => {
    if (window.localStorage.getItem("dark")) {
      return JSON.parse(window.localStorage.getItem("dark"));
    }
    return (
      !!window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    );
  };

  const setTheme = (value) => {
    window.localStorage.setItem("dark", value);
  };

  const updateQueryString = (key, value) => {
    let url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.history.pushState({}, "", url);
  };

  const removeQueryString = (key) => {
    let url = new URL(window.location.href);

    if (!url.searchParams.get(key)) return;

    url.searchParams.delete(key);
    window.history.pushState({}, "", url);
  };

  return {
    menu: "Dashboard",
    loading: true,
    isDark: false,
    async init() {
      this.$store.checkUpdate.checkUpdate();
      await this.$store.plugins.init();

      this.$nextTick(() => {
        autoAnimate(document.getElementById("plugin-menu-list"));
      });

      this.renderQueryTab();
    },
    toggleTheme() {
      this.isDark = !this.isDark;
      setTheme(this.isDark);
    },
    setLightTheme() {
      this.isDark = false;
      setTheme(this.isDark);
    },
    setDarkTheme() {
      this.isDark = true;
      setTheme(this.isDark);
    },
    isSettingsPanelOpen: false,
    openSettingsPanel() {
      this.isSettingsPanelOpen = true;
      this.$nextTick(() => {
        this.$refs.settingsPanel.focus();
      });
    },
    isNotificationsPanelOpen: false,
    openNotificationsPanel() {
      this.isNotificationsPanelOpen = true;
      this.$nextTick(() => {
        this.$refs.notificationsPanel.focus();
      });
    },
    isSearchPanelOpen: false,
    openSearchPanel() {
      this.isSearchPanelOpen = true;
      this.$nextTick(() => {
        this.$refs.searchInput.focus();
      });
    },
    isMobileSubMenuOpen: false,
    openMobileSubMenu() {
      this.isMobileSubMenuOpen = true;
      this.$nextTick(() => {
        this.$refs.mobileSubMenu.focus();
      });
    },
    isMobileMainMenuOpen: false,
    openMobileMainMenu() {
      this.isMobileMainMenuOpen = true;
      this.$nextTick(() => {
        this.$refs.mobileMainMenu.focus();
      });
    },
    toggleMainMenu(page, menu = "Dashboard") {
      this.menu = menu;
      this.$store.plugins.page = page;

      switch (menu) {
        case "Plugin":
          updateQueryString("tab", page);
          break;
        default:
          removeQueryString("tab");
          break;
      }
    },
    async renderQueryTab() {
      const queryTab = new URLSearchParams(window.location.search).get("tab");

      if (queryTab) {
        const activePlugins = Object.values(this.$store.plugins.activePlugins);

        if (
          activePlugins.length >= 1 &&
          activePlugins.find((plugin) => plugin.page == queryTab)
        ) {
          this.menu = "Plugin";
          await loadAjax(queryTab);
          this.$store.plugins.page = queryTab;
        }
      }
    },
  };
};

function pluginMenu() {
  return {
    page: "dashboard",
    plugins: [],
    init() {
      autoAnimate(document.getElementById("plugin-menu-list"));
    },
  };
}

function dashboard() {
  return {
    tab: "plugin-installed",
    activeTabClass:
      "ojt-inline-block ojt-p-4 ojt-w-full  ojt-bg-gray-50 hover:ojt-bg-gray-100 focus:ojt-outline-none ojt-text-primary-600 hover:ojt-text-primary-600 ojt-border-primary-600 dark:ojt-border-primary-500",
    inactiveTabClass:
      "ojt-inline-block ojt-p-4 ojt-w-full ojt-bg-gray-50 hover:ojt-bg-gray-100 focus:ojt-outline-none ojt-text-gray-500 hover:ojt-text-gray-600 ojt-border-gray-100 hover:ojt-border-gray-300",
  };
}

function checkUpdate() {
  return {
    updateAvailable: false,
    data: {},
    checkUpdate: async function () {
      try {
        let res = await fetch(
          "https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v1/ojtplugin/check_update",
          {
            mode: "cors",
          }
        );
        let ojtPlugin = await res.json();

        this.data = ojtPlugin;

        if (ojtPlugin.latest_version > ojtPluginVersion) {
          this.updateAvailable = true;
        }
      } catch (error) {}
    },
    doUpdate() {
      Swal.fire({
        title: "Are you sure want to Update Plugin?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, update it!",
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          const formData = new FormData();
          formData.append("ojtPlugin", JSON.stringify(this.data));

          return fetch(currentUrl + "updatePanel", {
            method: "POST",
            body: formData,
          })
            .then((response) => {
              return response.json();
            })
            .catch((error) => {
              Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading(),
      }).then((result) => {
        if (result.isConfirmed) {
          // show success message then reload page
          Swal.fire(result.value.msg).then(() => {
            if (result.value.error) {
              return;
            }
            location.reload();
          });
        }
      });
    },
  };
}

function pluginInstalled() {
  return {
    async init() {
      this.$nextTick(() => {
        // if (this.$refs.tablepluginlist) {
        //   autoAnimate(this.$refs.tablepluginlist, {
        //     duration: 300,
        //     // Easing for motion (default: 'ease-in-out')
        //     // easing: "ease-in-out",
        //   });
        // }
      });
    },
  };
}

function pluginGallery() {
  return {
    loading: true,
    error: false,
    filter: {
      type: "all",
      category: "all",
    },
    search: "",
    plugins: [],
    async init() {
      this.loading = true;
      try {
        await this.fetchPlugins();
        this.$nextTick(() => {
          if (this.$refs.gallerylist) {
            autoAnimate(this.$refs.gallerylist, {
              // duration: 500,
              // Easing for motion (default: 'ease-in-out')
              easing: "ease-in-out",
            });
          }
        });
      } catch (error) {
      } finally {
        this.loading = false;
      }
    },
    async reload() {
      this.error = false;
      this.fetchPlugins();
    },
    async fetchPlugins() {
      this.loading = true;

      try {
        let res = await fetch(currentUrl + "getPluginGalleryList");

        let response = await res.json();

        if (response.error) {
          throw response;
        }

        this.plugins = response;
      } catch (error) {
        this.loading = false;
        this.error = true;
        ajaxError(error);
        return;
      } finally {
        this.loading = false;
      }
    },
    get list() {
      let plugins = this.plugins.filter((plugin) => {
        let search = true;
        let type = true;
        let category = true;

        if (this.search) {
          search = plugin.name
            .toLowerCase()
            .includes(this.search.toLowerCase());
        }
        if (this.filter.type != "all") {
          type = plugin.type.toLowerCase() == this.filter.type;
        }
        if (this.filter.category != "all") {
          category = plugin.category.toLowerCase() == this.filter.category;
        }

        return search && type && category;
      });

      return plugins;
    },
    reset() {
      this.search = "";
      this.filter.type = "all";
      this.filter.category = "all";
    },
    async installPlugin(plugin) {
      try {
        switch (plugin.category) {
          case "PAID":
            input = "text";
            inputAttributes = {
              required: "",
            };
            inputLabel = "License key from the plugin you purchase.";
            break;
          default:
            input = null;
            inputAttributes = {};
            inputLabel = null;

            break;
        }

        let result = await Swal.fire({
          title: `Install ${plugin.name} ?`,
          icon: "warning",
          input: input,
          inputValue: plugin.license,
          inputLabel: inputLabel,
          inputAttributes: inputAttributes,
          inputPlaceholder: "Insert License Key.",
          customClass: {
            title: "!ojt-text-lg",
            inputLabel: "!ojt-text-base !ojt-justify-start",
          },
          showCancelButton: true,
          // confirmButtonColor: "#d33",
          // cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, install!",
          showLoaderOnConfirm: true,
          preConfirm: (license) => {
            if (plugin.category != "PAID") {
              license = null;
            }

            return this.submitInstall(plugin, license);
          },
          allowOutsideClick: () => !Swal.isLoading(),
        });

        if (!result.isConfirmed) return;

        if (result.value.error) throw result.value.msg;

        this.$store.plugins.fetchInstalledPlugin();
        this.plugins = this.plugins.map((plug) =>
          plug.token === plugin.token
            ? { ...plug, installed: true, update: false }
            : plug
        );
        ajaxResponse(result.value);

        return;
      } catch (error) {
        console.log(error);
        ajaxError({
          error: 1,
          msg: error,
        });
      }
    },
    async updatePlugin(plugin) {
      try {
        let result = await Swal.fire({
          title: `Update ${plugin.name} ?`,
          icon: "warning",
          customClass: {
            title: "!ojt-text-lg",
            inputLabel: "!ojt-text-base !ojt-justify-start",
          },
          showCancelButton: true,
          // confirmButtonColor: "#d33",
          // cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, update!",
          showLoaderOnConfirm: true,
          preConfirm: () => this.submitInstall(plugin, null, true),
          allowOutsideClick: () => !Swal.isLoading(),
        });

        if (!result.isConfirmed) return;

        if (result.value.error) throw result.value.msg;

        this.$store.plugins.fetchInstalledPlugin();
        this.plugins = this.plugins.map((plug) =>
          plug.token === plugin.token
            ? { ...plug, installed: true, update: false }
            : plug
        );
        ajaxResponse(result.value);

        return;
      } catch (error) {
        console.log(error);
        ajaxError({
          error: 1,
          msg: error,
        });
      }
    },
    async submitInstall(plugin, license = null, isUpdate = false) {
      const formData = new FormData();
      formData.append("plugin", JSON.stringify(plugin));
      if (license) {
        formData.append("license", license);
      }
      if (isUpdate) {
        formData.append("update", isUpdate);
      }

      try {
        let response = await fetch(currentUrl + "installPlugin", {
          method: "POST",
          body: formData,
        });

        if (!response.ok)
          // or check for response.status
          throw new Error(response.statusText);

        return await response.json();
      } catch (error) {
        return {
          error: 1,
          msg: "There is a problem with the installed plugin, please contact us.",
        };
      }
    },
  };
}

function pluginExclusive() {
  return {
    loading: true,
    error: false,
    plugins: [],
    async init() {
      this.loading = true;
      try {
        await this.fetchPlugins();
        this.$nextTick(() => {
          if (this.$refs.gallerylist) {
            autoAnimate(this.$refs.gallerylist, {
              // duration: 500,
              // Easing for motion (default: 'ease-in-out')
              easing: "ease-in-out",
            });
          }
        });
      } catch (error) {
      } finally {
        this.loading = false;
      }
    },
    async fetchPlugins() {
      this.loading = true;

      try {
        let res = await fetch(currentUrl + "getExclusivePlugins");

        let response = await res.json();

        if (response.error) {
          throw response;
        }

        this.plugins = response;
      } catch (error) {
        this.loading = false;
        this.error = true;
        ajaxError(error);
        return;
      } finally {
        this.loading = false;
      }
    },
    get list() {
      let plugins = this.plugins.filter((plugin) => {
        let search = true;
        let type = true;
        let category = true;

        if (this.search) {
          search = plugin.name
            .toLowerCase()
            .includes(this.search.toLowerCase());
        }
        if (this.filter.type != "all") {
          type = plugin.type.toLowerCase() == this.filter.type;
        }
        if (this.filter.category != "all") {
          category = plugin.category.toLowerCase() == this.filter.category;
        }

        return search && type && category;
      });

      return plugins;
    },
  };
}

function modalPlugin() {
  return {
    show: false,
    plugin: null,
    resetSetting: false,
    loading: false,
    installing: false,
    uninstalling: false,
    updating: false,
    key: "",
    close() {
      this.show = false;
      this.key = "";
      this.loading = false;
      this.resetSetting = false;
    },
    showPlugin(plugin) {
      this.plugin = plugin;
      this.show = true;
    },
    async installPlugin(plugin, update = false) {
      if (this.key == "" && !update) {
        Toast.fire({
          icon: "info",
          title: "Please insert license Key ..",
        });
        return;
      }

      if (this.loading == true) {
        Toast.fire({
          icon: "info",
          title: "Please Wait, still Processing ...",
        });
        return;
      }

      this.loading = true;

      const formData = new FormData();
      formData.append("plugin", JSON.stringify(plugin));
      formData.append("license", this.key);
      if (update) {
        formData.append("update", true);
      }

      let response = await fetch(currentUrl + "installPlugin", {
        method: "POST",
        body: formData,
      }).catch(function (error) {
        this.loading = false;
        ajaxError(error);
        return;
      });

      let data = await response.json();
      if (data.error == 1) {
        this.loading = false;
        console.log(data);
        ajaxError(data);
        return;
      }

      ajaxResponse(data);

      alpineComponent("pluginGallery").plugins = alpineComponent(
        "pluginGallery"
      ).plugins.map((plug) =>
        plug.token === plugin.token ? { ...plug, installed: true } : plug
      );

      this.close();

      this.$store.plugins.fetchInstalledPlugin();
    },
    async uninstall(plugin) {
      if (this.loading == true) {
        Toast.fire({
          icon: "info",
          title: "Please Wait, still Processing ...",
        });
        return;
      }

      this.loading = true;

      const formData = new FormData();
      formData.append("plugin", JSON.stringify(plugin));
      formData.append("resetSetting", this.resetSetting);

      let response = await fetch(currentUrl + "uninstallPlugin", {
        method: "POST",
        body: formData,
      }).catch(function (error) {
        this.loading = false;
        ajaxError(error);
      });

      let data = await response.json();

      ajaxResponse(data);

      alpineComponent("pluginGallery").plugins = alpineComponent(
        "pluginGallery"
      ).plugins.map((plug) =>
        plug.token === plugin.token ? { ...plug, installed: false } : plug
      );

      this.close();

      this.$store.plugins.fetchInstalledPlugin();
    },
    isValidURL(string) {
      var res = string.match(
        /(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g
      );
      return res !== null;
    },
  };
}
