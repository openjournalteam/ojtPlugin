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
    scheduleLoading: false,
    scheduleRequestInFlight: false,
    init() {
      this.loadJobs();
      this.loadSchedules();
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
    async loadSchedules() {
      if (this.scheduleRequestInFlight) return;
      this.scheduleRequestInFlight = true;
      this.scheduleLoading = true;

      try {
        const response = await fetch(currentUrl + "getSchedules", {
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });
        const payload = await response.json();
        if (!response.ok || payload.error) {
          throw new Error(payload.msg || "Unable to load schedules.");
        }

        this.schedules = (payload.data || []).map((schedule) => ({
          ...schedule,
          enabled: schedule.enabled === true || schedule.enabled === 1,
          cron: schedule.cron || "* * * * *",
          timezone: schedule.timezone || "UTC",
          human: schedule.human || "Custom schedule",
          next: schedule.next || "—",
          last: schedule.last && schedule.last !== "—" ? schedule.last : "Never scheduled",
          historyCount: Number(schedule.historyCount || 0),
          history: [],
          historyLoaded: false,
          historyLoading: false,
          historyOpen: false,
          lastStatus: schedule.lastStatus || "",
          lastStatusLabel: schedule.lastStatusLabel || "",
          failedCount: Number(schedule.failedCount || 0),
        }));
      } catch (error) {
        this.error = error.message || "Unable to load schedules.";
        if (typeof Toast !== "undefined") {
          Toast.fire({ icon: "error", title: this.error });
        }
      } finally {
        this.scheduleRequestInFlight = false;
        this.scheduleLoading = false;
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
      this.selectedJob = null;
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
    async sendScheduleAction(action, schedule, fields = {}) {
      if (!schedule) return false;

      const formData = new FormData();
      formData.append("action", action);
      formData.append("csrfToken", this.csrfToken);
      formData.append("scheduleId", String(schedule.id));
      Object.entries(fields).forEach(([key, value]) => formData.append(key, String(value)));

      try {
        const response = await fetch(currentUrl + "scheduleAction", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });
        const payload = await response.json();
        if (!response.ok || payload.error) {
          throw new Error(payload.msg || "Unable to apply the schedule action.");
        }
        if (typeof Toast !== "undefined") {
          Toast.fire({ icon: "success", title: payload.msg || "Schedule updated." });
        }
        await this.loadSchedules();
        return true;
      } catch (error) {
        if (typeof Toast !== "undefined") {
          Toast.fire({ icon: "error", title: error.message || "Unable to apply the schedule action." });
        }
        return false;
      }
    },
    async loadScheduleHistory(schedule) {
      if (!schedule || schedule.historyLoading) return;
      schedule.historyLoading = true;
      try {
        const params = new URLSearchParams({
          scheduleId: String(schedule.id),
          page: "1",
          pageSize: "50",
        });
        const response = await fetch(currentUrl + "getScheduleHistory?" + params.toString(), {
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });
        const payload = await response.json();
        if (!response.ok || payload.error) {
          throw new Error(payload.msg || "Unable to load schedule history.");
        }
        const data = payload.data || {};
        schedule.history = data.runs || [];
        schedule.historyTotal = Number((data.pagination || {}).total || schedule.history.length);
        schedule.historyLoaded = true;
        return true;
      } catch (error) {
        if (typeof Toast !== "undefined") {
          Toast.fire({ icon: "error", title: error.message || "Unable to load schedule history." });
        }
        return false;
      } finally {
        schedule.historyLoading = false;
      }
    },
    toggleScheduleHistory(schedule) {
      if (!schedule) return;
      if (schedule.historyLoaded) {
        schedule.historyOpen = !schedule.historyOpen;
        return;
      }
      this.loadScheduleHistory(schedule);
    },
    async openScheduleHistory(schedule) {
      if (!schedule) return;
      if (!schedule.historyLoaded) {
        const loaded = await this.loadScheduleHistory(schedule);
        if (!loaded) return;
      }
      if (typeof Swal === "undefined") return;

      const runs = schedule.history || [];
      const statusStyles = {
        dispatching: "background:#faf5ff;color:#7c3aed",
        queued: "background:#f3f4f6;color:#4b5563",
        processing: "background:#faf5ff;color:#7c3aed",
        paused: "background:#f3f4f6;color:#4b5563",
        done: "background:#f0fdf4;color:#15803d",
        failed: "background:#fff1f2;color:#be123c",
      };
      const rows = runs.length
        ? runs.map((run) => {
          const style = statusStyles[run.status] || statusStyles.queued;
          const failureText = run.status === "failed"
            ? (run.failureCause ? run.failureCause + (run.error ? ": " + run.error : "") : (run.error || "Run failed"))
            : "";
          return "<div style=\"display:flex;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid #f1f5f9;text-align:left\">" +
            "<span style=\"flex:0 0 auto;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:700;" + style + "\">" + this.escapeHtml(run.statusLabel) + "</span>" +
            "<span style=\"flex:0 0 auto;color:#64748b;font-size:12px\">" + this.escapeHtml(run.trigger === "manual" ? "Manual" : "Automatic") + "</span>" +
            "<span style=\"flex:1;min-width:0;color:#475569;font-size:12px\">" + this.escapeHtml(run.startedAt !== "—" ? run.startedAt : run.queuedAt) + "</span>" +
            "<span style=\"flex:0 0 auto;color:#94a3b8;font-size:12px\">" + this.escapeHtml(run.duration) + "</span>" +
            (failureText ? "<span style=\"flex:0 1 220px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#be123c;font-size:11px\" title=\"" + this.escapeHtml(failureText) + "\">" + this.escapeHtml(failureText) + "</span>" : "") +
            "<button type=\"button\" data-schedule-run-detail=\"" + run.id + "\" style=\"flex:0 0 auto;border:1px solid #e2e8f0;border-radius:6px;background:#fff;padding:5px 8px;color:#475569;font-size:11px;font-weight:600;cursor:pointer\" title=\"View run details\">Details</button>" +
            "</div>";
        }).join("")
        : "<div style=\"padding:28px 0;text-align:center;color:#94a3b8;font-size:13px\">This schedule has not run yet.</div>";

      Swal.fire({
        title: this.escapeHtml(schedule.name) + " history",
        html: "<div style=\"text-align:left\">" +
          "<p style=\"margin:0 0 12px;color:#64748b;font-size:13px\">Showing " + runs.length + " of " + (schedule.historyTotal || 0) + " runs.</p>" +
          "<div style=\"max-height:360px;overflow:auto\">" + rows + "</div>" +
          "</div>",
        showConfirmButton: true,
        confirmButtonText: "Close",
        confirmButtonColor: "#7c3aed",
        width: 700,
        didOpen: () => {
          document.querySelectorAll("[data-schedule-run-detail]").forEach((button) => {
            button.addEventListener("click", () => {
              const run = runs.find((item) => String(item.id) === String(button.getAttribute("data-schedule-run-detail")));
              if (run) this.viewScheduleRun(run, schedule);
            });
          });
        },
      });
    },
    scheduleRunStatusClasses(status) {
      return {
        dispatching: "ojt-bg-primary-50 ojt-text-primary-700",
        queued: "ojt-bg-gray-100 ojt-text-gray-600",
        processing: "ojt-bg-primary-50 ojt-text-primary-700",
        paused: "ojt-bg-gray-100 ojt-text-gray-600",
        done: "ojt-bg-success-50 ojt-text-success-700",
        failed: "ojt-bg-danger-50 ojt-text-danger-700",
      }[status] || "ojt-bg-gray-100 ojt-text-gray-600";
    },
    scheduleRunTriggerLabel(trigger) {
      return trigger === "manual" ? "Run manually" : "Scheduled run";
    },
    scheduleLastStatusClasses(status) {
      return {
        dispatching: "ojt-bg-primary-50 ojt-text-primary-700",
        manual_queued: "ojt-bg-primary-50 ojt-text-primary-700",
        queued: "ojt-bg-gray-100 ojt-text-gray-600",
        processing: "ojt-bg-primary-50 ojt-text-primary-700",
        done: "ojt-bg-success-50 ojt-text-success-700",
        failed: "ojt-bg-danger-50 ojt-text-danger-700",
        paused: "ojt-bg-gray-100 ojt-text-gray-600",
      }[status] || "ojt-bg-gray-100 ojt-text-gray-600";
    },
    escapeHtml(value) {
      return String(value === null || typeof value === "undefined" ? "" : value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#039;");
    },
    viewScheduleRun(run, schedule) {
      if (!run || typeof Swal === "undefined") return;
      const details = this.escapeHtml(JSON.stringify(run.details || {}, null, 2));
      const error = run.error
        ? "<div style=\"margin:12px 0 0;padding:10px 12px;border-radius:8px;background:#fff1f2;color:#be123c;text-align:left;font-size:12px\"><strong>Error:</strong> " + this.escapeHtml(run.error) + "</div>"
        : "";
      Swal.fire({
        title: this.escapeHtml(schedule ? schedule.name : "Scheduled run"),
        html: "<div style=\"text-align:left\">" +
          "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;font-size:13px\">" +
          "<div><span style=\"color:#9ca3af;display:block\">Status</span><strong>" + this.escapeHtml(run.statusLabel) + "</strong></div>" +
          (run.failureCause ? "<div><span style=\"color:#9ca3af;display:block\">Cause</span><strong>" + this.escapeHtml(run.failureCause) + "</strong></div>" : "") +
          "<div><span style=\"color:#9ca3af;display:block\">Started</span><strong>" + this.escapeHtml(run.startedAt) + "</strong></div>" +
          "<div><span style=\"color:#9ca3af;display:block\">Finished</span><strong>" + this.escapeHtml(run.finishedAt || run.stoppedAt) + "</strong></div>" +
          "<div><span style=\"color:#9ca3af;display:block\">Duration</span><strong>" + this.escapeHtml(run.duration) + "</strong></div>" +
          "<div><span style=\"color:#9ca3af;display:block\">Attempts</span><strong>" + this.escapeHtml(run.attempts) + "</strong></div>" +
          "<div><span style=\"color:#9ca3af;display:block\">Started by</span><strong>" + this.escapeHtml(this.scheduleRunTriggerLabel(run.trigger)) + "</strong></div>" +
          "</div>" +
          error +
          "<div style=\"margin-top:12px;color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase\">Run details</div>" +
          "<pre style=\"max-height:260px;overflow:auto;margin:6px 0 0;padding:12px;border-radius:8px;background:#f8fafc;color:#334155;text-align:left;font-size:11px;white-space:pre-wrap\">" + details + "</pre>" +
          "</div>",
        showConfirmButton: true,
        confirmButtonText: "Close",
        confirmButtonColor: "#7c3aed",
        width: 620,
      });
    },
    setScheduleEnabled(schedule, enabled) {
      return this.sendScheduleAction("toggle", schedule, { enabled: enabled ? "1" : "0" });
    },
    confirmScheduleToggle(schedule) {
      const enabled = !schedule.enabled;
      const copy = enabled
        ? { title: "Enable this schedule?", text: schedule.name + " will run automatically.", confirm: "Enable schedule", color: "#7c3aed", icon: "question" }
        : { title: "Pause this schedule?", text: schedule.name + " will not run until it is enabled again.", confirm: "Pause schedule", color: "#6b7280", icon: "warning" };

      if (typeof Swal === "undefined") return this.setScheduleEnabled(schedule, enabled);
      Swal.fire({
        title: copy.title,
        text: copy.text,
        icon: copy.icon,
        showCancelButton: true,
        confirmButtonText: copy.confirm,
        cancelButtonText: "Cancel",
        confirmButtonColor: copy.color,
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) this.setScheduleEnabled(schedule, enabled);
      });
    },
    confirmRunSchedule(schedule) {
      if (!schedule) return;
      const run = () => this.sendScheduleAction("run_now", schedule);
      if (typeof Swal === "undefined") return run();
      Swal.fire({
        title: "Run this schedule now?",
        text: schedule.name + " will be added to the Background Jobs queue.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Run now",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#7c3aed",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) run();
      });
    },
    scheduleEditorValues(schedule) {
      const parts = String(schedule.cron || "").trim().split(/\s+/);
      const values = { frequency: "daily", time: "02:00", day: "1" };
      if (parts.length !== 5) return values;

      if (/^\*\/\d+$/.test(parts[0]) && parts[1] === "*" && parts[2] === "*" && parts[3] === "*" && parts[4] === "*") {
        const minutes = parts[0].slice(2);
        if (["5", "10", "15", "30"].includes(minutes)) values.frequency = "minutes-" + minutes;
      } else if (parts[0] === "0" && /^\*\/\d+$/.test(parts[1]) && parts[2] === "*" && parts[3] === "*" && parts[4] === "*") {
        const hours = parts[1].slice(2);
        if (["1", "2", "6", "12"].includes(hours)) values.frequency = "hours-" + hours;
      } else if (/^\d+$/.test(parts[0]) && /^\d+$/.test(parts[1]) && parts[2] === "*" && parts[3] === "*" && parts[4] === "*") {
        values.frequency = "daily";
        values.time = String(parts[1]).padStart(2, "0") + ":" + String(parts[0]).padStart(2, "0");
      } else if (/^\d+$/.test(parts[0]) && /^\d+$/.test(parts[1]) && parts[2] === "*" && parts[3] === "*" && /^[0-7]$/.test(parts[4])) {
        values.frequency = "weekly";
        values.time = String(parts[1]).padStart(2, "0") + ":" + String(parts[0]).padStart(2, "0");
        values.day = parts[4] === "7" ? "0" : parts[4];
      } else if (/^\d+$/.test(parts[0]) && /^\d+$/.test(parts[1]) && parts[2] === "1" && parts[3] === "*" && parts[4] === "*") {
        values.frequency = "monthly";
        values.time = String(parts[1]).padStart(2, "0") + ":" + String(parts[0]).padStart(2, "0");
      }

      return values;
    },
    scheduleCronFromEditor(frequency, time, day) {
      const timeParts = String(time || "02:00").split(":");
      const hour = Math.min(23, Math.max(0, parseInt(timeParts[0], 10) || 0));
      const minute = Math.min(59, Math.max(0, parseInt(timeParts[1], 10) || 0));
      if (frequency === "weekly") return minute + " " + hour + " * * " + (day || "1");
      if (frequency === "monthly") return minute + " " + hour + " 1 * *";
      if (frequency === "daily") return minute + " " + hour + " * * *";
      if (frequency.indexOf("minutes-") === 0) return "*/" + frequency.split("-")[1] + " * * * *";
      if (frequency.indexOf("hours-") === 0) return "0 */" + frequency.split("-")[1] + " * * *";
      return null;
    },
    scheduleEditorSummary(frequency, time, day) {
      const labels = {
        "minutes-5": "every 5 minutes",
        "minutes-10": "every 10 minutes",
        "minutes-15": "every 15 minutes",
        "minutes-30": "every 30 minutes",
        "hours-1": "every hour",
        "hours-2": "every 2 hours",
        "hours-6": "every 6 hours",
        "hours-12": "every 12 hours",
      };
      if (labels[frequency]) return "Runs " + labels[frequency] + ".";
      const readableTime = (() => {
        const parts = String(time || "02:00").split(":");
        const hour = parseInt(parts[0], 10) || 0;
        const minute = String(parts[1] || "00").padStart(2, "0");
        const suffix = hour >= 12 ? "PM" : "AM";
        const displayHour = hour % 12 || 12;
        return displayHour + ":" + minute + " " + suffix;
      })();
      if (frequency === "weekly") {
        const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        return "Runs every " + (days[parseInt(day, 10) || 0]) + " at " + readableTime + ".";
      }
      if (frequency === "monthly") return "Runs on the 1st of every month at " + readableTime + ".";
      return "Runs every day at " + readableTime + ".";
    },
    editSchedule(schedule) {
      if (!schedule || typeof Swal === "undefined") return;
      const values = this.scheduleEditorValues(schedule);
      const save = (editorValues) => {
        const cron = this.scheduleCronFromEditor(editorValues.frequency, editorValues.time, editorValues.day);
        if (!cron) return false;
        return this.sendScheduleAction("update", schedule, {
          cron,
          timezone: schedule.timezone,
        });
      };
      const frequencyOptions = [
        ["minutes-5", "Every 5 minutes"],
        ["minutes-10", "Every 10 minutes"],
        ["minutes-15", "Every 15 minutes"],
        ["minutes-30", "Every 30 minutes"],
        ["hours-1", "Every hour"],
        ["hours-2", "Every 2 hours"],
        ["hours-6", "Every 6 hours"],
        ["hours-12", "Every 12 hours"],
        ["daily", "Every day"],
        ["weekly", "Every week"],
        ["monthly", "Every month"],
      ];
      const frequencyMarkup = frequencyOptions.map(([value, label]) =>
        "<option value=\"" + value + "\"" + (values.frequency === value ? " selected" : "") + ">" + label + "</option>"
      ).join("");
      const dayMarkup = [
        ["0", "Sunday"], ["1", "Monday"], ["2", "Tuesday"], ["3", "Wednesday"],
        ["4", "Thursday"], ["5", "Friday"], ["6", "Saturday"],
      ].map(([value, label]) =>
        "<option value=\"" + value + "\"" + (values.day === value ? " selected" : "") + ">" + label + "</option>"
      ).join("");

      Swal.fire({
        title: "Edit schedule",
        html: "<div style=\"text-align:left\">" +
          "<p style=\"margin:0 0 16px;color:#6b7280;font-size:14px\">Choose a simple repeat pattern. Extra choices appear only when needed.</p>" +
          "<label for=\"ojt-schedule-frequency\" style=\"display:block;margin:0 0 6px;font-weight:600\">How often?</label>" +
          "<select id=\"ojt-schedule-frequency\" class=\"swal2-select\" style=\"display:flex;width:100%;margin:0 0 14px\">" + frequencyMarkup + "</select>" +
          "<div id=\"ojt-schedule-time-field\">" +
            "<label for=\"ojt-schedule-time\" style=\"display:block;margin:0 0 6px;font-weight:600\">What time?</label>" +
            "<input id=\"ojt-schedule-time\" type=\"time\" class=\"swal2-input\" value=\"" + values.time + "\" style=\"width:100%;margin:0 0 14px\">" +
          "</div>" +
          "<div id=\"ojt-schedule-day-field\">" +
            "<label for=\"ojt-schedule-day\" style=\"display:block;margin:0 0 6px;font-weight:600\">Which day?</label>" +
            "<select id=\"ojt-schedule-day\" class=\"swal2-select\" style=\"display:flex;width:100%;margin:0 0 14px\">" + dayMarkup + "</select>" +
          "</div>" +
          "<div id=\"ojt-schedule-summary\" style=\"padding:10px 12px;border-radius:8px;background:#faf5ff;color:#7c3aed;font-size:13px;font-weight:600\"></div>" +
          "</div>",
        showCancelButton: true,
        confirmButtonText: "Save schedule",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#7c3aed",
        reverseButtons: true,
        focusConfirm: false,
        didOpen: () => {
          const frequency = document.getElementById("ojt-schedule-frequency");
          const time = document.getElementById("ojt-schedule-time");
          const day = document.getElementById("ojt-schedule-day");
          const timeField = document.getElementById("ojt-schedule-time-field");
          const dayField = document.getElementById("ojt-schedule-day-field");
          const summary = document.getElementById("ojt-schedule-summary");
          const updateFields = () => {
            const usesTime = ["daily", "weekly", "monthly"].includes(frequency.value);
            const usesDay = frequency.value === "weekly";
            timeField.style.display = usesTime ? "block" : "none";
            dayField.style.display = usesDay ? "block" : "none";
            summary.textContent = this.scheduleEditorSummary(frequency.value, time.value, day.value);
          };
          frequency.addEventListener("change", updateFields);
          time.addEventListener("input", updateFields);
          day.addEventListener("change", updateFields);
          updateFields();
        },
        preConfirm: () => {
          const frequency = document.getElementById("ojt-schedule-frequency").value;
          const time = document.getElementById("ojt-schedule-time").value;
          const day = document.getElementById("ojt-schedule-day").value;
          const cron = this.scheduleCronFromEditor(frequency, time, day);
          if (!cron) {
            Swal.showValidationMessage("Please choose a valid repeat option.");
            return false;
          }
          return { frequency, time, day };
        },
      }).then((result) => {
        if (result.isConfirmed) save(result.value);
      });
    },
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
