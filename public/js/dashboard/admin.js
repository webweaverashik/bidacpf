"use strict";

/**
 * Admin Dashboard Module
 */
const AdminDashboard = (function () {
      // State
      const state = {
            currentBranchId: '',
            selectedDate: null,
            selectedAttendanceDate: null,
            selectedBatchId: '',
            costPeriod: 'month',
            charts: {
                  collection: null,
                  costPie: null
            },
            flatpickr: null,
            attendanceFlatpickr: null,
            refreshInterval: null,
            initialized: false,
            isAdmin: true
      };

      // Configuration
      const config = {
            refreshIntervalMs: 300000, // 5 minutes
            apiBaseUrl: '/dashboard',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
      };

      /**
       * Format currency in Bangladeshi Taka
       */
      const formatCurrency = (amount) => {
            return '৳' + Number(amount || 0).toLocaleString('en-BD');
      };

      /**
       * Format date for display (e.g., "29 Jan 2026")
       */
      const formatDisplayDate = (date) => {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
      };

      /**
       * Format date for API (YYYY-MM-DD)
       */
      const formatApiDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
      };

      /**
       * Check if date is today
       */
      const isToday = (date) => {
            const today = new Date();
            return date.getDate() === today.getDate() &&
                  date.getMonth() === today.getMonth() &&
                  date.getFullYear() === today.getFullYear();
      };

      /**
       * Show empty state
       */
      const showEmptyState = (elementId, icon, message) => {
            const el = document.getElementById(elementId);
            if (el) {
                  el.innerHTML = `
                <div class="text-center py-10 text-muted">
                    <i class="bi ${icon} fs-2x mb-3 d-block opacity-50"></i>
                    <span class="text-gray-500">${message}</span>
                </div>
            `;
            }
      };

      /**
       * API request helper
       */
      const fetchData = async (endpoint, params = {}) => {
            const queryParams = new URLSearchParams(params);
            if (state.currentBranchId) {
                  queryParams.set('branch_id', state.currentBranchId);
            }

            try {
                  const response = await fetch(`${config.apiBaseUrl}/${endpoint}?${queryParams.toString()}`, {
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  });

                  if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                  }

                  return await response.json();
            } catch (error) {
                  console.error(`Error fetching ${endpoint}:`, error);
                  return { success: false, error: error.message };
            }
      };

      /**
       * Load summary statistics
       */
      const loadSummary = async () => {
            const result = await fetchData('summary');

            if (result.success) {
                  const d = result.data;

                  // Update student stats
                  updateElement('statTotalStudents', d.students.total.toLocaleString());
                  updateElement('statActiveStudents', d.students.active.toLocaleString());
                  updateElement('statPendingStudents', d.students.pending.toLocaleString());

                  // Update invoice stats
                  updateElement('statDueInvoices', d.invoices.due_count.toLocaleString());
                  updateElement('statDueAmount', formatCurrency(d.invoices.due_amount) + ' due');

                  // Update collection stats
                  updateElement('statTodayCollection', formatCurrency(d.collections.today));
                  updateElement('statMonthCollection', formatCurrency(d.collections.month));

                  // Update cost and approvals
                  updateElement('statTodayCost', formatCurrency(d.today_cost));
                  updateElement('statPendingApprovals', d.pending_approvals);
                  updateElement('approvalBadge', d.pending_approvals);
            }
      };

      /**
       * Update element text content
       */
      const updateElement = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                  el.textContent = value;
            }
      };

      /**
       * Load collection statistics and render chart
       */
      const loadCollectionStats = async () => {
            const dateStr = formatApiDate(state.selectedDate);
            const result = await fetchData('collection-stats', { date: dateStr });

            if (result.success) {
                  const data = result.data;

                  // Update chart title based on selected date
                  const titleEl = document.getElementById('collectionChartTitle');
                  const subtitleEl = document.getElementById('collectionChartSubtitle');
                  const userSubtitleEl = document.getElementById('userCollectionSubtitle');
                  if (titleEl) {
                        titleEl.textContent = data.is_today ? "Today's Collection" : `Collection on ${data.display_date}`;
                  }
                  if (subtitleEl) {
                        subtitleEl.textContent = `Hourly breakdown - ${formatCurrency(data.selected_date_collection)} total`;
                  }
                  if (userSubtitleEl) {
                        userSubtitleEl.textContent = data.is_today ? "Today's performance" : `Performance on ${data.display_date}`;
                  }

                  const hourlyData = data.hourly_collection || [];
                  const categories = hourlyData.map(i => i.hour);
                  const values = hourlyData.map(i => {
                        const val = parseFloat(i.amount);
                        return isNaN(val) ? 0 : val;
                  });

                  // Check if there's any data
                  const hasData = values.some(v => v > 0);

                  const chartEl = document.querySelector("#collectionChart");
                  if (!chartEl) return;

                  // Destroy existing chart if it exists
                  if (state.charts.collection) {
                        state.charts.collection.destroy();
                        state.charts.collection = null;
                  }

                  // Always clear the container first
                  chartEl.innerHTML = '';

                  // If no data, show empty state
                  if (!hasData) {
                        chartEl.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 py-10">
                        <i class="bi bi-bar-chart text-gray-300 fs-3x mb-3"></i>
                        <span class="text-gray-500 fs-6">No collections recorded${data.is_today ? ' today' : ' on ' + data.display_date}</span>
                        <span class="text-gray-400 fs-7">Transactions will appear here when payments are received</span>
                    </div>
                `;
                        // Still render user collection (might be empty too)
                        renderUserCollection(data.user_wise_collection || [], data.is_admin);
                        return;
                  }

                  const chartOptions = {
                        series: [{
                              name: 'Collection',
                              data: values
                        }],
                        chart: {
                              type: 'bar',
                              height: 300,
                              toolbar: { show: false },
                              fontFamily: 'inherit'
                        },
                        colors: ['#50cd89'],
                        plotOptions: {
                              bar: {
                                    borderRadius: 4,
                                    columnWidth: '60%',
                                    distributed: false
                              }
                        },
                        dataLabels: { enabled: false },
                        xaxis: {
                              categories: categories,
                              labels: {
                                    style: { fontSize: '11px', colors: '#7e8299' },
                                    rotate: -45,
                                    rotateAlways: false
                              },
                              axisBorder: { show: false },
                              axisTicks: { show: false }
                        },
                        yaxis: {
                              min: 0,
                              labels: {
                                    formatter: v => formatCurrency(v),
                                    style: { fontSize: '11px', colors: '#7e8299' }
                              }
                        },
                        tooltip: {
                              y: { formatter: v => formatCurrency(v) }
                        },
                        grid: {
                              borderColor: '#f1f1f4',
                              strokeDashArray: 4,
                              padding: { left: 10, right: 10 }
                        },
                        states: {
                              hover: {
                                    filter: { type: 'darken', value: 0.9 }
                              }
                        }
                  };

                  // Create new chart
                  state.charts.collection = new ApexCharts(chartEl, chartOptions);
                  state.charts.collection.render();

                  // Render user-wise collection with links for admin
                  renderUserCollection(data.user_wise_collection || [], data.is_admin);
            }
      };

      /**
       * Render user collection list
       */
      const renderUserCollection = (users, isAdmin = true) => {
            const container = document.getElementById('userCollectionList');
            if (!container) return;

            if (users.length === 0) {
                  showEmptyState('userCollectionList', 'bi-inbox', 'No collections for selected date');
                  return;
            }

            // Calculate total
            const grandTotal = users.reduce((sum, user) => sum + (parseFloat(user.total) || 0), 0);

            let html = '<div class="table-responsive"><table class="table table-row-dashed align-middle gy-4 my-0"><tbody>';
            users.forEach(user => {
                  const initial = user.user_name.charAt(0).toUpperCase();
                  const userLink = isAdmin && user.user_id
                        ? `<a href="/settlements/${user.user_id}" target="_blank" class="text-gray-800 text-hover-primary fw-semibold">${user.user_name}</a>`
                        : `<span class="text-gray-800 fw-semibold">${user.user_name}</span>`;

                  html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-3">
                                <span class="symbol-label bg-light-primary text-primary fw-bold fs-5">${initial}</span>
                            </div>
                            ${userLink}
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="text-success fw-bold fs-5">${formatCurrency(user.total)}</span>
                    </td>
                </tr>
            `;
            });
            // Add total row
            html += `
                <tr class="border-top border-gray-300">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-3">
                                <span class="symbol-label bg-light-success text-success fw-bold fs-5">Σ</span>
                            </div>
                            <span class="text-gray-900 fw-bold">Total</span>
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="text-primary fw-bold fs-4">${formatCurrency(grandTotal)}</span>
                    </td>
                </tr>
            `;
            html += '</tbody></table></div>';
            container.innerHTML = html;
      };

      /**
       * Load invoice statistics
       */
      const loadInvoiceStats = async () => {
            const result = await fetchData('invoice-stats');
            if (result.success) {
                  renderTopDueStudents(result.data.top_due_students || []);
            }
      };

      /**
       * Render top due students list
       */
      const renderTopDueStudents = (students) => {
            const container = document.getElementById('topDueStudentsList');
            if (!container) return;

            if (students.length === 0) {
                  showEmptyState('topDueStudentsList', 'bi-check-circle', 'No due invoices found');
                  return;
            }

            let html = `
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 rounded-start min-w-50px">#</th>
                            <th class="min-w-200px">Student</th>
                            <th class="text-center min-w-80px">Invoices</th>
                            <th class="text-end pe-4 rounded-end min-w-120px">Total Due</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            students.forEach((s, i) => {
                  const rankBadge = i < 3
                        ? `<span class="badge badge-circle badge-light-danger fw-bold">${i + 1}</span>`
                        : `<span class="text-gray-600 fw-semibold">${i + 1}</span>`;

                  html += `
                <tr>
                    <td class="ps-4">${rankBadge}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <a href="/students/${s.student_id}" target="_blank" class="text-gray-800 text-hover-primary fw-bold">${s.name}</a>
                            <span class="text-muted fs-7">${s.student_unique_id}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-warning fw-bold">${s.invoice_count}</span>
                    </td>
                    <td class="text-end pe-4">
                        <span class="text-danger fw-bold fs-6">${formatCurrency(s.total_due)}</span>
                    </td>
                </tr>
            `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
      };

      /**
       * Load pending approvals (Admin only)
       */
      const loadPendingApprovals = async () => {
            const result = await fetchData('pending-discounted');
            if (result.success) {
                  renderPendingApprovals(result.data || []);
            }
      };

      /**
       * Render pending approvals list
       */
      const renderPendingApprovals = (transactions) => {
            const container = document.getElementById('pendingApprovalsList');
            if (!container) return;

            // Update badge count
            const badge = document.getElementById('approvalBadge');
            if (badge) {
                  badge.textContent = transactions.length;
            }

            if (transactions.length === 0) {
                  showEmptyState('pendingApprovalsList', 'bi-check-all', 'No pending approvals');
                  return;
            }

            let html = `
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 rounded-start min-w-150px">Transaction</th>
                            <th class="min-w-150px">Student</th>
                            <th class="text-end pe-4 rounded-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            transactions.forEach(txn => {
                  html += `
                <tr id="approval_row_${txn.id}">
                    <td class="ps-4">
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bold">${txn.voucher_no}</span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge badge-light-warning">${formatCurrency(txn.amount_paid)}</span>
                                <span class="text-muted fs-8">${txn.created_at}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-semibold">${txn.student_name}</span>
                            <span class="text-muted fs-7">${txn.student_id}</span>
                        </div>
                    </td>
                    <td class="text-end pe-4">
                        <a href="/invoices/${txn.invoice_id}" target="_blank" class="btn btn-icon btn-sm btn-light-primary me-1" data-bs-toggle="tooltip" title="View Invoice">
                            <i class="bi bi-eye fs-6"></i>
                        </a>
                        <button class="btn btn-icon btn-sm btn-light-success me-1 approve-btn" data-id="${txn.id}" data-voucher="${txn.voucher_no}" data-bs-toggle="tooltip" title="Approve">
                            <i class="bi bi-check-lg fs-6"></i>
                        </button>
                        <button class="btn btn-icon btn-sm btn-light-danger delete-btn" data-id="${txn.id}" data-voucher="${txn.voucher_no}" data-bs-toggle="tooltip" title="Delete">
                            <i class="bi bi-trash fs-6"></i>
                        </button>
                    </td>
                </tr>
            `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;

            // Re-initialize tooltips
            const tooltipTriggerList = container.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

            // Attach event listeners
            container.querySelectorAll('.approve-btn').forEach(btn => {
                  btn.addEventListener('click', () => approveTransaction(btn.dataset.id, btn.dataset.voucher));
            });
            container.querySelectorAll('.delete-btn').forEach(btn => {
                  btn.addEventListener('click', () => deleteTransaction(btn.dataset.id, btn.dataset.voucher));
            });
      };

      /**
       * Approve a transaction with SweetAlert
       */
      const approveTransaction = async (id, voucherNo) => {
            const result = await Swal.fire({
                  title: 'Approve Transaction?',
                  html: `Are you sure you want to approve transaction <strong>${voucherNo}</strong>?`,
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonColor: '#50cd89',
                  cancelButtonColor: '#f1416c',
                  confirmButtonText: 'Yes, Approve!',
                  cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            try {
                  const response = await fetch(`/transactions/${id}/approve`, {
                        method: 'POST',
                        headers: {
                              'Content-Type': 'application/json',
                              'X-CSRF-TOKEN': config.csrfToken,
                              'Accept': 'application/json'
                        }
                  });

                  if (response.ok) {
                        document.getElementById(`approval_row_${id}`)?.remove();
                        loadSummary();

                        // Check if list is empty
                        const tbody = document.querySelector('#pendingApprovalsList tbody');
                        if (tbody && tbody.children.length === 0) {
                              showEmptyState('pendingApprovalsList', 'bi-check-all', 'No pending approvals');
                        }

                        toastr.success(`Transaction ${voucherNo} approved successfully!`);
                  } else {
                        toastr.error('Failed to approve transaction');
                  }
            } catch (e) {
                  console.error('Failed to approve:', e);
                  toastr.error('Failed to approve transaction');
            }
      };

      /**
       * Delete a transaction with SweetAlert
       */
      const deleteTransaction = async (id, voucherNo) => {
            const result = await Swal.fire({
                  title: 'Delete Transaction?',
                  html: `Are you sure you want to delete transaction <strong>${voucherNo}</strong>?<br><small class="text-muted">This action cannot be undone.</small>`,
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#f1416c',
                  cancelButtonColor: '#7e8299',
                  confirmButtonText: 'Yes, Delete!',
                  cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            try {
                  const response = await fetch(`/transactions/${id}`, {
                        method: 'DELETE',
                        headers: {
                              'X-CSRF-TOKEN': config.csrfToken,
                              'Accept': 'application/json'
                        }
                  });

                  if (response.ok) {
                        document.getElementById(`approval_row_${id}`)?.remove();
                        loadSummary();

                        // Check if list is empty
                        const tbody = document.querySelector('#pendingApprovalsList tbody');
                        if (tbody && tbody.children.length === 0) {
                              showEmptyState('pendingApprovalsList', 'bi-check-all', 'No pending approvals');
                        }

                        toastr.success(`Transaction ${voucherNo} deleted successfully!`);
                  } else {
                        toastr.error('Failed to delete transaction');
                  }
            } catch (e) {
                  console.error('Failed to delete:', e);
                  toastr.error('Failed to delete transaction');
            }
      };

      /**
       * Load cost statistics
       */
      const loadCostStats = async () => {
            const result = await fetchData('cost-stats', { period: state.costPeriod });

            if (result.success) {
                  const data = result.data;
                  updateElement('costPeriodLabel', `${data.start_date} - ${data.end_date}`);

                  const breakdown = data.cost_type_breakdown || [];

                  const chartEl = document.querySelector("#costPieChart");

                  if (breakdown.length > 0 && chartEl) {
                        const labels = breakdown.map(i => i.type_name);
                        const values = breakdown.map(i => parseFloat(i.total));

                        const chartOptions = {
                              series: values,
                              chart: {
                                    type: 'donut',
                                    height: 380,
                                    fontFamily: 'inherit'
                              },
                              labels: labels,
                              colors: ['#009ef7', '#50cd89', '#ffc700', '#f1416c', '#7239ea', '#69b3a2', '#e4e6ef'],
                              legend: {
                                    position: 'bottom',
                                    fontSize: '13px',
                                    fontWeight: 500,
                                    labels: {
                                          colors: '#5e6278'
                                    }
                              },
                              plotOptions: {
                                    pie: {
                                          donut: {
                                                size: '65%',
                                                labels: {
                                                      show: true,
                                                      name: {
                                                            show: true,
                                                            fontSize: '14px',
                                                            fontWeight: 600
                                                      },
                                                      value: {
                                                            show: true,
                                                            fontSize: '18px',
                                                            fontWeight: 700,
                                                            formatter: v => formatCurrency(v)
                                                      },
                                                      total: {
                                                            show: true,
                                                            label: 'Total Cost',
                                                            fontSize: '13px',
                                                            fontWeight: 500,
                                                            color: '#7e8299',
                                                            formatter: () => formatCurrency(data.total_cost)
                                                      }
                                                }
                                          }
                                    }
                              },
                              tooltip: {
                                    y: { formatter: v => formatCurrency(v) }
                              },
                              stroke: {
                                    width: 2
                              },
                              responsive: [{
                                    breakpoint: 480,
                                    options: {
                                          chart: { height: 320 },
                                          legend: { position: 'bottom' }
                                    }
                              }]
                        };

                        // Destroy existing chart
                        if (state.charts.costPie) {
                              state.charts.costPie.destroy();
                              state.charts.costPie = null;
                        }

                        state.charts.costPie = new ApexCharts(chartEl, chartOptions);
                        state.charts.costPie.render();
                  } else if (chartEl) {
                        if (state.charts.costPie) {
                              state.charts.costPie.destroy();
                              state.charts.costPie = null;
                        }
                        chartEl.innerHTML = `
                    <div class="text-center py-10 text-muted">
                        <i class="bi bi-pie-chart fs-2x mb-3 d-block opacity-50"></i>
                        <span class="text-gray-500">No cost data available</span>
                    </div>
                `;
                  }

                  renderCostTable(breakdown, data.total_cost);
            }
      };

      /**
       * Render cost type table
       */
      const renderCostTable = (breakdown, total) => {
            const container = document.getElementById('costTypeTable');
            if (!container) return;

            if (breakdown.length === 0) {
                  showEmptyState('costTypeTable', 'bi-inbox', 'No cost records found');
                  return;
            }

            let html = `
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 rounded-start">Cost Type</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end pe-4 rounded-end">Share</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            breakdown.forEach(item => {
                  const pct = total > 0 ? ((item.total / total) * 100).toFixed(1) : 0;
                  html += `
                <tr>
                    <td class="ps-4">
                        <span class="text-gray-800 fw-semibold">${item.type_name}</span>
                    </td>
                    <td class="text-end">
                        <span class="fw-bold">${formatCurrency(item.total)}</span>
                    </td>
                    <td class="text-end pe-4">
                        <span class="badge badge-light-primary">${pct}%</span>
                    </td>
                </tr>
            `;
            });

            html += `
                    <tr class="bg-light">
                        <td class="ps-4 fw-bold text-gray-800">Total</td>
                        <td class="text-end fw-bold text-primary fs-5">${formatCurrency(total)}</td>
                        <td class="pe-4"></td>
                    </tr>
                </tbody></table></div>
        `;
            container.innerHTML = html;
      };

      /**
       * Load attendance statistics
       */
      const loadAttendanceStats = async () => {
            const dateStr = formatApiDate(state.selectedAttendanceDate);
            const params = { date: dateStr };
            if (state.selectedBatchId) {
                  params.batch_id = state.selectedBatchId;
            }

            const result = await fetchData('attendance-stats', params);

            if (result.success) {
                  const d = result.data;

                  // Update date label
                  const dateLabel = document.getElementById('attendanceDateLabel');
                  if (dateLabel) {
                        dateLabel.textContent = d.is_today ? "Today's summary" : `Summary for ${d.display_date}`;
                  }

                  // Update today's stats
                  updateElement('attPresent', d.today.present || 0);
                  updateElement('attAbsent', d.today.absent || 0);
                  updateElement('attLate', d.today.late || 0);

                  // Render batch tabs
                  renderBatchTabs(d.filters.batches || []);

                  // Render class-wise data
                  renderAttendanceList('attendanceByClassList', d.class_wise, 'class_name');
            }
      };

      /**
       * Render batch tabs
       */
      const renderBatchTabs = (batches) => {
            const container = document.getElementById('attendanceBatchTabs');
            if (!container) return;

            let html = `
            <li class="nav-item">
                <a class="nav-link fw-semibold ${!state.selectedBatchId ? 'active' : ''}" data-batch-id="" href="javascript:void(0)">All</a>
            </li>
        `;

            batches.forEach(batch => {
                  html += `
                <li class="nav-item">
                    <a class="nav-link fw-semibold ${state.selectedBatchId == batch.id ? 'active' : ''}" data-batch-id="${batch.id}" href="javascript:void(0)">${batch.name}</a>
                </li>
            `;
            });

            container.innerHTML = html;

            // Re-attach event listeners
            container.querySelectorAll('.nav-link').forEach(tab => {
                  tab.addEventListener('click', (e) => {
                        e.preventDefault();
                        container.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
                        e.target.classList.add('active');
                        state.selectedBatchId = e.target.dataset.batchId || '';
                        loadAttendanceStats();
                  });
            });
      };

      /**
       * Render attendance list
       */
      const renderAttendanceList = (containerId, items, nameKey) => {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (!items || items.length === 0) {
                  container.innerHTML = '<div class="text-center py-5 text-muted"><span class="text-gray-500">No attendance data for selected date</span></div>';
                  return;
            }

            let html = `
            <table class="table table-row-dashed align-middle gy-2 my-0">
                <thead>
                    <tr class="text-muted fw-semibold fs-8 text-uppercase">
                        <th>Class</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Late</th>
                    </tr>
                </thead>
                <tbody>
        `;

            items.forEach(item => {
                  html += `
                <tr>
                    <td class="text-gray-800 fw-semibold fs-7">${item[nameKey] || 'Unknown'}</td>
                    <td class="text-center"><span class="badge badge-light-success">${item.present || 0}</span></td>
                    <td class="text-center"><span class="badge badge-light-danger">${item.absent || 0}</span></td>
                    <td class="text-center"><span class="badge badge-light-warning">${item.late || 0}</span></td>
                </tr>
            `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
      };

      /**
       * Load recent transactions
       */
      const loadRecentTransactions = async () => {
            const result = await fetchData('recent-transactions');
            if (result.success) {
                  renderRecentTransactions(result.data || []);
            }
      };

      /**
       * Render recent transactions
       */
      const renderRecentTransactions = (transactions) => {
            const container = document.getElementById('recentTransactionsList');
            if (!container) return;

            if (transactions.length === 0) {
                  showEmptyState('recentTransactionsList', 'bi-inbox', 'No recent transactions');
                  return;
            }

            let html = `
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 rounded-start min-w-200px">Student</th>
                            <th class="min-w-120px">Invoice</th>
                            <th class="min-w-80px text-center">Type</th>
                            <th class="text-end pe-4 rounded-end min-w-100px">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            transactions.forEach(txn => {
                  const badgeClass = txn.payment_type === 'full' ? 'success' : (txn.payment_type === 'partial' ? 'warning' : 'info');
                  const paymentTypeLabel = txn.payment_type.charAt(0).toUpperCase() + txn.payment_type.slice(1);

                  html += `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-3">
                                <span class="symbol-label bg-light-${badgeClass}">
                                    <i class="bi bi-receipt text-${badgeClass} fs-5"></i>
                                </span>
                            </div>
                            <div class="d-flex flex-column">
                              <a href="/students/${txn.student_id}" target="_blank" class="text-gray-800 text-hover-primary fw-semibold">${txn.student_name}</a>
                                <span class="text-muted fs-7">${txn.created_at_diff}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="/invoices/${txn.invoice_id}" target="_blank" class="text-gray-800 text-hover-primary fw-semibold">${txn.invoice_number}</a>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${badgeClass}">${paymentTypeLabel}</span>
                    </td>
                    <td class="text-end pe-4">
                        <span class="text-success fw-bold">${formatCurrency(txn.amount_paid)}</span>
                    </td>
                </tr>
            `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
      };

      /**
       * Initialize flatpickr date picker for collection
       */
      const initDatePicker = () => {
            const dateInput = document.getElementById('collectionDatePicker');
            if (!dateInput) return;

            // Initialize state.selectedDate to today
            state.selectedDate = new Date();

            // Set initial display value
            dateInput.value = formatDisplayDate(state.selectedDate);

            // Initialize flatpickr
            state.flatpickr = flatpickr(dateInput, {
                  dateFormat: 'd M Y',
                  defaultDate: state.selectedDate,
                  maxDate: 'today',
                  disableMobile: true,
                  onChange: function (selectedDates) {
                        if (selectedDates.length > 0) {
                              state.selectedDate = selectedDates[0];
                              dateInput.value = formatDisplayDate(state.selectedDate);
                              updateNextButtonState();
                              loadCollectionStats();
                        }
                  }
            });

            // Previous day button
            document.getElementById('prevDateBtn')?.addEventListener('click', () => {
                  const newDate = new Date(state.selectedDate);
                  newDate.setDate(newDate.getDate() - 1);
                  state.selectedDate = newDate;
                  state.flatpickr.setDate(newDate);
                  dateInput.value = formatDisplayDate(state.selectedDate);
                  updateNextButtonState();
                  loadCollectionStats();
            });

            // Next day button
            document.getElementById('nextDateBtn')?.addEventListener('click', () => {
                  const today = new Date();
                  today.setHours(0, 0, 0, 0);

                  const currentDate = new Date(state.selectedDate);
                  currentDate.setHours(0, 0, 0, 0);

                  if (currentDate < today) {
                        const newDate = new Date(state.selectedDate);
                        newDate.setDate(newDate.getDate() + 1);
                        state.selectedDate = newDate;
                        state.flatpickr.setDate(newDate);
                        dateInput.value = formatDisplayDate(state.selectedDate);
                        updateNextButtonState();
                        loadCollectionStats();
                  }
            });

            // Update next button state initially
            updateNextButtonState();
      };

      /**
       * Update next button disabled state
       */
      const updateNextButtonState = () => {
            const nextBtn = document.getElementById('nextDateBtn');
            if (!nextBtn) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const currentDate = new Date(state.selectedDate);
            currentDate.setHours(0, 0, 0, 0);

            if (currentDate >= today) {
                  nextBtn.classList.add('disabled');
                  nextBtn.setAttribute('disabled', 'disabled');
            } else {
                  nextBtn.classList.remove('disabled');
                  nextBtn.removeAttribute('disabled');
            }
      };

      /**
       * Initialize flatpickr date picker for attendance
       */
      const initAttendanceDatePicker = () => {
            const dateInput = document.getElementById('attendanceDatePicker');
            if (!dateInput) return;

            // Initialize state.selectedAttendanceDate to today
            state.selectedAttendanceDate = new Date();

            // Set initial display value
            dateInput.value = formatDisplayDate(state.selectedAttendanceDate);

            // Initialize flatpickr
            state.attendanceFlatpickr = flatpickr(dateInput, {
                  dateFormat: 'd M Y',
                  defaultDate: state.selectedAttendanceDate,
                  maxDate: 'today',
                  disableMobile: true,
                  onChange: function (selectedDates) {
                        if (selectedDates.length > 0) {
                              state.selectedAttendanceDate = selectedDates[0];
                              dateInput.value = formatDisplayDate(state.selectedAttendanceDate);
                              updateAttendanceNextButtonState();
                              loadAttendanceStats();
                        }
                  }
            });

            // Previous day button
            document.getElementById('prevAttDateBtn')?.addEventListener('click', () => {
                  const newDate = new Date(state.selectedAttendanceDate);
                  newDate.setDate(newDate.getDate() - 1);
                  state.selectedAttendanceDate = newDate;
                  state.attendanceFlatpickr.setDate(newDate);
                  dateInput.value = formatDisplayDate(state.selectedAttendanceDate);
                  updateAttendanceNextButtonState();
                  loadAttendanceStats();
            });

            // Next day button
            document.getElementById('nextAttDateBtn')?.addEventListener('click', () => {
                  const today = new Date();
                  today.setHours(0, 0, 0, 0);

                  const currentDate = new Date(state.selectedAttendanceDate);
                  currentDate.setHours(0, 0, 0, 0);

                  if (currentDate < today) {
                        const newDate = new Date(state.selectedAttendanceDate);
                        newDate.setDate(newDate.getDate() + 1);
                        state.selectedAttendanceDate = newDate;
                        state.attendanceFlatpickr.setDate(newDate);
                        dateInput.value = formatDisplayDate(state.selectedAttendanceDate);
                        updateAttendanceNextButtonState();
                        loadAttendanceStats();
                  }
            });

            // Update next button state initially
            updateAttendanceNextButtonState();
      };

      /**
       * Update attendance next button disabled state
       */
      const updateAttendanceNextButtonState = () => {
            const nextBtn = document.getElementById('nextAttDateBtn');
            if (!nextBtn) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const currentDate = new Date(state.selectedAttendanceDate);
            currentDate.setHours(0, 0, 0, 0);

            if (currentDate >= today) {
                  nextBtn.classList.add('disabled');
                  nextBtn.setAttribute('disabled', 'disabled');
            } else {
                  nextBtn.classList.remove('disabled');
                  nextBtn.removeAttribute('disabled');
            }
      };

      /**
       * Initialize all dashboard components
       */
      const initDashboard = () => {
            loadSummary();
            loadCollectionStats();
            loadInvoiceStats();
            loadPendingApprovals();
            loadCostStats();
            loadAttendanceStats();
            loadRecentTransactions();
      };

      /**
       * Setup event listeners
       */
      const setupEventListeners = () => {
            // Branch tab switching
            document.querySelectorAll('#branchTabs .nav-link').forEach(tab => {
                  tab.addEventListener('click', (e) => {
                        // Update active state
                        document.querySelectorAll('#branchTabs .nav-link').forEach(t => {
                              t.classList.remove('active', 'btn-light-primary');
                              t.classList.add('btn-light');
                        });
                        e.target.classList.remove('btn-light');
                        e.target.classList.add('active', 'btn-light-primary');

                        // Update branch ID and reload
                        state.currentBranchId = e.target.dataset.branchId || '';

                        // Destroy existing charts before reinitializing
                        if (state.charts.collection) {
                              state.charts.collection.destroy();
                              state.charts.collection = null;
                        }
                        if (state.charts.costPie) {
                              state.charts.costPie.destroy();
                              state.charts.costPie = null;
                        }

                        initDashboard();
                  });
            });

            // Cost period switching
            document.querySelectorAll('[data-period]').forEach(btn => {
                  btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
                        e.target.classList.add('active');
                        state.costPeriod = e.target.dataset.period;
                        loadCostStats();
                  });
            });
      };

      /**
       * Start auto-refresh
       */
      const startAutoRefresh = () => {
            state.refreshInterval = setInterval(() => {
                  loadSummary();
                  loadRecentTransactions();
                  loadPendingApprovals();
            }, config.refreshIntervalMs);
      };

      /**
       * Stop auto-refresh
       */
      const stopAutoRefresh = () => {
            if (state.refreshInterval) {
                  clearInterval(state.refreshInterval);
            }
      };

      /**
       * Initialize the module
       */
      const init = () => {
            // Mark dashboard link as active
            const dashboardLink = document.getElementById('dashboard_link');
            if (dashboardLink) {
                  dashboardLink.classList.add('active');
            }

            // Set initial branch ID from config or first tab
            if (window.DASHBOARD_CONFIG && window.DASHBOARD_CONFIG.initialBranchId) {
                  state.currentBranchId = window.DASHBOARD_CONFIG.initialBranchId;
            } else {
                  const firstTab = document.querySelector('#branchTabs .nav-link.active');
                  if (firstTab) {
                        state.currentBranchId = firstTab.dataset.branchId || '';
                  }
            }

            // Initialize date pickers first
            initDatePicker();
            initAttendanceDatePicker();

            // Initialize dashboard
            initDashboard();

            // Setup event listeners
            setupEventListeners();

            // Start auto-refresh
            startAutoRefresh();

            state.initialized = true;
      };

      // Public API
      return {
            init,
            refresh: initDashboard,
            setBranch: (branchId) => {
                  state.currentBranchId = branchId;
                  initDashboard();
            },
            stopAutoRefresh
      };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
      AdminDashboard.init();
});