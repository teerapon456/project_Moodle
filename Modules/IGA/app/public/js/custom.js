
document.addEventListener('DOMContentLoaded', function () {
  /* ---------- Password toggle (เดิมของคุณ) ---------- */
  function setupPasswordToggles() {
    const togglePasswordButtons = document.querySelectorAll('.password-toggle');
    togglePasswordButtons.forEach(button => {
      button.addEventListener('click', function () {
        const targetId = this.dataset.target;
        const passwordInput = document.getElementById(targetId);
        if (!passwordInput) return;
        const eyeIcon = this.querySelector('i');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        if (eyeIcon) {
          eyeIcon.classList.toggle('fa-eye');
          eyeIcon.classList.toggle('fa-eye-slash');
        }
      });
    });
  }

  function handlePasswordToggleWithAlert() {
    const toggleElements = document.querySelectorAll('.password-toggle');
    toggleElements.forEach(toggle => {
      toggle.style.display = 'flex';
      toggle.style.position = 'absolute';
      toggle.style.top = '50%';
    });
  }

  // ------------ Question modal handlers (เดิมของคุณ) ------------
  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-question-btn')) {
      const sectionId = e.target.closest('.add-question-btn').dataset.sectionId;
      document.getElementById('questionSectionId').value = sectionId;
      document.getElementById('questionForm').reset();
      document.getElementById('questionAction').value = 'add';
      document.getElementById('addQuestionModalLabel').innerHTML = '<i class="fas fa-plus-circle me-2"></i> เพิ่มคำถามใหม่';
      document.getElementById('questionId').value = '';
      document.getElementById('optionsList').innerHTML = '';
      document.getElementById('optionsContainer').style.display = 'none';
      const modal = new bootstrap.Modal(document.getElementById('addQuestionModal'));
      modal.show();
    }

    if (e.target.closest('.edit-question-btn')) {
      const btn = e.target.closest('.edit-question-btn');
      document.getElementById('questionId').value = btn.dataset.id;
      document.getElementById('questionSectionId').value = btn.dataset.sectionId;
      document.getElementById('questionText').value = btn.dataset.questionText;
      document.getElementById('questionType').value = btn.dataset.questionType;
      document.getElementById('questionScore').value = btn.dataset.score;
      document.getElementById('questionOrder').value = btn.dataset.questionOrder;
      document.getElementById('questionCategory').value = btn.dataset.categoryId || '';
      document.getElementById('isCritical').checked = btn.dataset.isCritical === '1';
      document.getElementById('questionAction').value = 'edit';
      document.getElementById('addQuestionModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i> แก้ไขคำถาม';

      const options = JSON.parse(btn.dataset.options || '[]');
      const optionsList = document.getElementById('optionsList');
      optionsList.innerHTML = '';
      if (options && options.length > 0) {
        options.forEach((option) => {
          addOptionField(option.is_correct === 1 || option.is_correct === '1', option.option_text, option.option_id);
        });
        document.getElementById('optionsContainer').style.display = 'block';
      } else {
        document.getElementById('optionsContainer').style.display = 'none';
      }

      const modal = new bootstrap.Modal(document.getElementById('addQuestionModal'));
      modal.show();
    }

    if (e.target.closest('#addOptionBtn')) {
      addOptionField();
    }
    if (e.target.closest('.remove-option')) {
      e.preventDefault();
      const inputGroup = e.target.closest('.input-group');
      if (inputGroup) inputGroup.remove();
    }
  });

  function addOptionField(isChecked = false, text = '', optionId = '') {
    const optionsList = document.getElementById('optionsList');
    const optionIndex = optionsList.children.length;
    const optionIdAttr = optionId ? `data-option-id="${optionId}"` : '';
    const optionHtml = `
      <div class="input-group mb-2" ${optionIdAttr}>
        <div class="input-group-text">
          <input class="form-check-input mt-0" type="radio" name="is_correct_option" value="${optionIndex}" ${isChecked ? 'checked' : ''}>
        </div>
        <input type="text" class="form-control" name="options[${optionIndex}][text]" value="${text}" required>
        <input type="hidden" name="options[${optionIndex}][option_id]" value="${optionId}">
        <button type="button" class="btn btn-outline-danger remove-option" title="Remove option">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
    const div = document.createElement('div');
    div.innerHTML = optionHtml.trim();
    optionsList.appendChild(div.firstChild);
  }

  // ---------------- Select2: safe init & guard ----------------
  const $ = window.jQuery; // ใช้ jQuery ที่มีอยู่บนหน้า
  function initSelect2Once(context) {
    if (!$ || !$.fn || !$.fn.select2) return false; // ยังไม่โหลดปลั๊กอิน -> บอกว่ายัง init ไม่ได้
    const $ctx = context ? $(context) : $(document);
    $ctx.find('.orgunitname-select2').each(function () {
      const $el = $(this);
      if ($el.data('select2')) return; // กัน init ซ้ำ
      try {
        $el.select2({
          width: '100%',
          placeholder: 'เลือกหน่วยงาน...',
          allowClear: true
        });
      } catch (err) {
        console.warn('Select2 init error on element:', this, err);
      }
    });
    return true;
  }

  // Retry จนกว่าจะมี select2 (เช่น script โหลดช้ากว่า custom.js)
  (function retryInitSelect2() {
    let attempts = 0;
    const MAX = 20;       // รวม 20 ครั้ง
    const INTERVAL = 250; // ทุก 250ms
    const timer = setInterval(() => {
      attempts++;
      if (initSelect2Once(document)) {
        clearInterval(timer);
      } else if (attempts >= MAX) {
        clearInterval(timer);
        console.warn('Select2 not found after retries. Skipped initialization safely.');
      }
    }, INTERVAL);
  })();

  // Observe DOM ที่เพิ่มใหม่ แล้วพยายาม init เฉพาะ nodes ที่เพิ่มมา
  const mo = new MutationObserver((muts) => {
    muts.forEach(m => {
      m.addedNodes.forEach(node => {
        // ถ้าตัวที่เพิ่มมาเป็น element และมี select2 class ข้างใน -> init เฉพาะส่วนนั้น
        if (node.nodeType === 1) {
          initSelect2Once(node);
        }
      });
    });
  });
  mo.observe(document.body, { childList: true, subtree: true });

  // ---------------- Init ส่วนอื่น ๆ ----------------
  setupPasswordToggles();
  handlePasswordToggleWithAlert();

  // สังเกตการเปลี่ยนแปลง alert ให้ปรับ position toggle (ตามโค้ดเดิม)
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.addedNodes.length || mutation.removedNodes.length) {
        handlePasswordToggleWithAlert();
      }
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });
});
