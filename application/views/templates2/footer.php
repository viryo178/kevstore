

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="<?= base_url() ?>assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="<?= base_url() ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= base_url() ?>assets/vendor/chart.js/chart.umd.js"></script>
  <script src="<?= base_url() ?>assets/vendor/echarts/echarts.min.js"></script>
  <script src="<?= base_url() ?>assets/vendor/quill/quill.js"></script>
  <script src="<?= base_url() ?>assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="<?= base_url() ?>assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="<?= base_url() ?>assets/vendor/php-email-form/validate.js"></script>

  <!-- Auto hide alerts after 5 seconds -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(function() {
          const bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
        }, 5000);
      });
    });
  </script>

  <!-- Toast container and helper -->
  <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1500; pointer-events: none;"></div>
  <script>
    function showToast(message, type = 'success', timeout = 5000) {
      const container = document.getElementById('toastContainer');
      if (!container) return;
      const toastId = 'toast-' + Date.now();
      const bg = (type === 'success') ? 'bg-success text-white' : (type === 'danger') ? 'bg-danger text-white' : 'bg-secondary text-white';
      const wrapper = document.createElement('div');
      wrapper.style.pointerEvents = 'auto';
      const toastEl = document.createElement('div');
      toastEl.id = toastId;
      toastEl.className = `toast align-items-center ${bg} border-0 mb-2`;
      toastEl.setAttribute('role', 'status');
      toastEl.setAttribute('aria-live', 'polite');
      toastEl.setAttribute('aria-atomic', 'true');
      const inner = document.createElement('div');
      inner.className = 'd-flex';
      const body = document.createElement('div');
      body.className = 'toast-body';
      body.textContent = message;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn-close btn-close-white me-2 m-auto';
      btn.setAttribute('data-bs-dismiss', 'toast');
      btn.setAttribute('aria-label', 'Close');
      inner.appendChild(body);
      inner.appendChild(btn);
      toastEl.appendChild(inner);
      wrapper.appendChild(toastEl);
      container.appendChild(wrapper);
      const bsToast = new bootstrap.Toast(toastEl, { delay: timeout });
      bsToast.show();
      toastEl.addEventListener('hidden.bs.toast', function() { wrapper.remove(); });
    }
  </script>

  <script>
    (function() {
      const style = document.createElement('style');
      style.textContent = `
        .date-display-wrap {
          position: relative;
          display: block;
        }

        .date-display-wrap > input[data-date-display="true"] {
          padding-right: 44px !important;
        }

        .date-display-button {
          position: absolute;
          top: 50%;
          right: 12px;
          transform: translateY(-50%);
          border: 0;
          background: transparent;
          color: #93c5fd;
          width: 28px;
          height: 28px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 0;
          cursor: pointer;
          pointer-events: none;
        }

        .date-display-native {
          position: absolute;
          top: 50%;
          right: 10px;
          transform: translateY(-50%);
          width: 32px;
          height: 32px;
          opacity: 0;
          cursor: pointer;
          border: 0;
          padding: 0;
        }
      `;
      document.head.appendChild(style);

      function isoToDisplay(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        return match ? `${match[3]}/${match[2]}/${match[1]}` : value;
      }

      function displayToIso(value) {
        const match = String(value || '').match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/);
        return match ? `${match[3]}-${match[2]}-${match[1]}` : '';
      }

      function attachDateButton(input) {
        if (input.closest('.date-display-wrap')) {
          return;
        }

        const wrapper = document.createElement('span');
        wrapper.className = 'date-display-wrap';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'date-display-button';
        button.innerHTML = '<i class="bi bi-calendar"></i>';
        wrapper.appendChild(button);

        const nativePicker = document.createElement('input');
        nativePicker.type = 'date';
        nativePicker.className = 'date-display-native';
        nativePicker.tabIndex = -1;
        wrapper.appendChild(nativePicker);

        nativePicker.addEventListener('focus', () => {
          nativePicker.value = displayToIso(input.value);
        });

        nativePicker.addEventListener('click', () => {
          nativePicker.value = displayToIso(input.value);
        });

        nativePicker.addEventListener('change', () => {
          input.value = isoToDisplay(nativePicker.value);
        });
      }

      function normalizeDateInputs(root = document) {
        const inputs = [];

        if (root.matches && root.matches('input[type="date"]:not(.date-display-native), input[data-date-display="true"]')) {
          inputs.push(root);
        }

        root.querySelectorAll('input[type="date"]:not(.date-display-native), input[data-date-display="true"]').forEach(input => inputs.push(input));

        inputs.forEach(input => {
          if (!input.dataset.dateDisplay) {
            input.dataset.dateDisplay = 'true';
            input.type = 'text';
            input.placeholder = 'DD/MM/YYYY';
            input.inputMode = 'numeric';
            input.autocomplete = 'off';
          }

          input.value = isoToDisplay(input.value);
          attachDateButton(input);
        });
      }

      document.addEventListener('DOMContentLoaded', () => normalizeDateInputs());
      document.addEventListener('shown.bs.modal', event => normalizeDateInputs(event.target));

      document.addEventListener('input', event => {
        const input = event.target;

        if (!input.matches('input[data-date-display="true"]')) {
          return;
        }

        let value = input.value.replace(/[^\d]/g, '').slice(0, 8);

        if (value.length >= 5) {
          value = `${value.slice(0, 2)}/${value.slice(2, 4)}/${value.slice(4)}`;
        } else if (value.length >= 3) {
          value = `${value.slice(0, 2)}/${value.slice(2)}`;
        }

        input.value = value;
      });

      const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
          mutation.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
              normalizeDateInputs(node);
            }
          });
        });
      });

      observer.observe(document.documentElement, {
        childList: true,
        subtree: true
      });
    })();
  </script>

  <script>
    (function() {
      const cronUrl = "<?= base_url('cron/send-expired-whatsapp/kevstore-expired-wa-00') ?>";
      const cronStorageKey = 'kevstoreExpiredWhatsappCronDate';

      function localDateKey(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }

      function runExpiredWhatsappCron() {
        const today = localDateKey(new Date());

        if (localStorage.getItem(cronStorageKey) === today) {
          return;
        }

        localStorage.setItem(cronStorageKey, today);

        fetch(cronUrl, {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store'
        }).catch(function() {
          localStorage.removeItem(cronStorageKey);
        });
      }

      function msUntilNextMidnight() {
        const now = new Date();
        const next = new Date(now);
        next.setDate(now.getDate() + 1);
        next.setHours(0, 0, 5, 0);
        return next.getTime() - now.getTime();
      }

      document.addEventListener('DOMContentLoaded', function() {
        runExpiredWhatsappCron();

        setTimeout(function triggerDailyCron() {
          runExpiredWhatsappCron();
          setTimeout(triggerDailyCron, 24 * 60 * 60 * 1000);
        }, msUntilNextMidnight());
      });
    })();
  </script>

  <!-- Template Main JS File -->
  <script src="<?= base_url() ?>assets/js/main.js"></script>

</body>

</html>
