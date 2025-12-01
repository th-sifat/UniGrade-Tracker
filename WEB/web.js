// web.js (simple UI wiring with Lab support; event delegation for per-row add buttons)
document.addEventListener('DOMContentLoaded', function() {

  // -----------------------------
  // 1. CHECK LOGIN STATUS (unchanged)
  // -----------------------------
  fetch("is_logged_in.php", { cache: 'no-store' })
    .then(res => res.text())
    .then(txt => {
      const headerActions = document.querySelector(".header-actions");
      if (!headerActions) return;

      const loggedIn = txt.trim() === '1';
      if (loggedIn) {
        headerActions.innerHTML = `
          <button class="btn dark">Dark Mode</button>
          <a href="logout.php" class="btn sign">Logout</a>
        `;
      } else {
        headerActions.innerHTML = `
          <button class="btn dark">Dark Mode</button>
          <a href="login.html" class="btn log">Log In</a>
          <a href="signup.html" class="btn sign">Sign Up</a>
        `;
      }
      setupDarkButton();
    })
    .catch(() => {
      setupDarkButton();
    });

  // -----------------------------
  // 2. DARK MODE BUTTON LABEL FIX
  // -----------------------------
  function setupDarkButton() {
    const darkBtn = document.querySelector(".btn.dark");
    if (!darkBtn) return;
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
      darkBtn.innerText = "Light Mode";
    } else {
      darkBtn.innerText = "Dark Mode";
    }

    darkBtn.onclick = function () {
      document.body.classList.toggle("dark");
      const isDark = document.body.classList.contains("dark");
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      darkBtn.innerText = isDark ? 'Light Mode' : 'Dark Mode';
    };
  }

  // Call once in case header already loaded
  setupDarkButton();

  // -------------------------------------------
  // ADD / REMOVE (delegated) - works for per-row buttons
  // -------------------------------------------
  const coursesContainer = document.getElementById('courses-container');
  const labTemplate = document.getElementById('lab-template');

  function attachRemove() {
    // IMPORTANT: count only the course-wrapper elements INSIDE coursesContainer
    const wrappersSelector = coursesContainer ? coursesContainer.querySelectorAll('.course-wrapper') : document.querySelectorAll('.course-wrapper');

    document.querySelectorAll('.remove-course').forEach(btn => {
      // remove any existing onclick to avoid double-binding
      btn.onclick = null;
      btn.addEventListener('click', function() {
        // re-query live wrappers inside coursesContainer when clicked
        const wrappers = coursesContainer ? coursesContainer.querySelectorAll('.course-wrapper') : document.querySelectorAll('.course-wrapper');

        if (wrappers.length > 1) {
          this.closest('.course-wrapper').remove();
        } else {
          // If it's the last visible card, clear its inputs instead of removing it
          const first = wrappers[0];
          if (first) {
            first.querySelectorAll('input').forEach(i => {
              // clear only user-editable inputs
              if (i.type === 'text' || i.type === 'number' || i.type === 'email' || i.type === 'password') {
                i.value = '';
              }
            });
          }
          alert('You must have at least one course. The last course was cleared.');
        }
      });
    });
  }
  attachRemove();

  // Event delegation: handle clicks for add-course and add-lab buttons anywhere
  document.addEventListener('click', function(e) {
    const addCourseBtn = e.target.closest('.btn-add-course');
    if (addCourseBtn) {
      // Add a normal course (clone the first normal template)
      const template = document.querySelector('.course-wrapper[data-type="normal"]');
      if (!template) return;
      const clone = template.cloneNode(true);
      clone.querySelectorAll('input').forEach(i => i.value = '');
      coursesContainer.appendChild(clone);
      attachRemove();
      return;
    }

    const addLabBtn = e.target.closest('.btn-add-lab');
    if (addLabBtn) {
      // Add a lab course
      if (!labTemplate) return;
      const node = labTemplate.firstElementChild.cloneNode(true);
      node.querySelectorAll('input').forEach(i => i.value = '');
      coursesContainer.appendChild(node);
      attachRemove();
      return;
    }
  });

  // -------------------------------------------
  // Grade mapping + SGPA calculation
  // -------------------------------------------
  function getGradePoint(marks) {
    if (marks >= 80) return 4.00;
    if (marks >= 75) return 3.75;
    if (marks >= 70) return 3.50;
    if (marks >= 65) return 3.25;
    if (marks >= 60) return 3.00;
    if (marks >= 55) return 2.75;
    if (marks >= 50) return 2.50;
    if (marks >= 45) return 2.25;
    if (marks >= 40) return 2.00;
    return 0.00;
  }

  const calculateBtn = document.getElementById('calculateBtn');
  const resultArea = document.getElementById('result-area');
  const sgpaValue = document.getElementById('sgpa-value');

  function calculateSGPA() {
    let totalCredits = 0, totalQualityPoints = 0;
    document.querySelectorAll('.course-wrapper').forEach(wrapper => {
      const credits = parseFloat(wrapper.querySelector('.credit-input')?.value) || 0;
      if (credits <= 0) return;

      const type = wrapper.dataset.type || 'normal';
      if (type === 'lab' || wrapper.classList.contains('lab-course')) {
        const attPercent = parseFloat(wrapper.querySelector('.lab-att-input')?.value) || 0;
        const attMarks = (attPercent / 100) * 10;
        const labFinal = parseFloat(wrapper.querySelector('.lab-final-input')?.value) || 0;
        const labPerf = parseFloat(wrapper.querySelector('.lab-perf-input')?.value) || 0;
        const labReport = parseFloat(wrapper.querySelector('.lab-report-input')?.value) || 0;
        const obtained = labFinal + labPerf + labReport + attMarks;
        const gp = getGradePoint(obtained);
        totalQualityPoints += gp * credits;
        totalCredits += credits;
      } else {
        const attPercent = parseFloat(wrapper.querySelector('.att-input')?.value) || 0;
        const attMarks = (attPercent / 100) * 10;
        let other = 0;
        wrapper.querySelectorAll('.mark-input').forEach(mi => other += parseFloat(mi.value) || 0);
        const obtained = attMarks + other;
        const gp = getGradePoint(obtained);
        totalQualityPoints += gp * credits;
        totalCredits += credits;
      }
    });

    if (totalCredits <= 0) { alert('Enter credits for at least one course.'); return null; }
    const sgpa = totalQualityPoints / totalCredits;
    if (resultArea) resultArea.style.display = 'block';
    if (sgpaValue) sgpaValue.innerText = sgpa.toFixed(2);
    return { sgpa: parseFloat(sgpa.toFixed(2)), totalCredits };
  }

  if (calculateBtn) calculateBtn.addEventListener('click', calculateSGPA);

  // ---------- Save result (plain text) ----------
  const saveBtn = document.getElementById('saveResultBtn');
  if (saveBtn) {
    saveBtn.addEventListener('click', function() {
      const out = calculateSGPA();
      if (!out) return;
      const sem = prompt('Enter semester number (e.g. 1):');
      if (!sem) return;
      const data = new URLSearchParams();
      data.append('semester_no', sem);
      data.append('sgpa', out.sgpa);
      data.append('total_credits', out.totalCredits);

      fetch('save_result.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data.toString()
      })
      .then(r => r.text())
      .then(txt => {
        const t = (txt || '').trim();
        if (t === 'OK') alert('Saved successfully!');
        else if (t.startsWith('ERROR:')) alert('Save failed: ' + t.substring(6));
        else alert('Unexpected server response: ' + t);
      })
      .catch(e => { alert('Save request failed. Are you logged in?'); console.error(e); });
    });
  }

});
