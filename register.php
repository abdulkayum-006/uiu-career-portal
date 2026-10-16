<?php
/**
 * UIU Career Portal - Registration Page
 * Dual registration portal for Students and Corporate Recruiters
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$app_root = get_app_root();
$page_title = 'Register | UIU Career Portal';
$page_desc = 'Register for the United International University Internship and Career Placement Portal.';

$error = '';
$success = '';
$account_type = $_GET['type'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $_POST['account_type'] ?? 'student';
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $pdo = get_db_connection();
        if ($pdo) {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'This email address is already registered in our system.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $role = ($account_type === 'company') ? 'company' : 'student';
                    $status = ($account_type === 'company') ? 'pending' : 'active'; // Companies require admin approval

                    $userStmt = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)");
                    $userStmt->execute([$email, $hashedPassword, $role, $status]);
                    $userId = $pdo->lastInsertId();

                    if ($role === 'student') {
                        $studentId = trim($_POST['student_id'] ?? '');
                        $fullName = trim($_POST['full_name'] ?? '');
                        $dept = trim($_POST['department'] ?? 'CSE');
                        $batch = trim($_POST['batch'] ?? 'CSE-50');
                        $cgpa = floatval($_POST['cgpa'] ?? 3.50);
                        $phone = trim($_POST['phone'] ?? '');

                        $sStmt = $pdo->prepare("INSERT INTO students (user_id, student_id, full_name, department, batch, cgpa, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $sStmt->execute([$userId, $studentId, $fullName, $dept, $batch, $cgpa, $phone]);

                        // Seed empty resume
                        $rStmt = $pdo->prepare("INSERT INTO resumes (student_id, full_name, email, phone, degree, university, cgpa, skills, projects, certifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $rStmt->execute([$pdo->lastInsertId(), $fullName, $email, $phone, "B.Sc. in {$dept}", "United International University", (string)$cgpa, "Web Development, Problem Solving", "Academic Capstone Project", "Undergraduate Enrollment"]);

                    } elseif ($role === 'company') {
                        $companyName = trim($_POST['company_name'] ?? '');
                        $sector = trim($_POST['sector'] ?? 'Software & IT');
                        $industry = trim($_POST['industry'] ?? 'Technology Services');
                        $location = trim($_POST['location'] ?? 'Dhaka, Bangladesh');
                        $website = trim($_POST['website'] ?? '');
                        $phone = trim($_POST['phone'] ?? '');
                        $desc = trim($_POST['description'] ?? '');

                        $cStmt = $pdo->prepare("INSERT INTO companies (user_id, name, sector, industry, location, website, email, phone, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                        $cStmt->execute([$userId, $companyName, $sector, $industry, $location, $website, $email, $phone, $desc]);
                    }

                    $pdo->commit();
                    $success = ($role === 'company') 
                        ? 'Company registered successfully! Your account is submitted for verification by the UIU Career Services Office.'
                        : 'Registration successful! You may now log in to access your student dashboard.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        } else {
            $success = 'Account created successfully in demo mode! You can now sign in.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="min-height: calc(100vh - 120px); display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.25rem; background: linear-gradient(135deg, #fff7ed 0%, #faf8f5 100%);">
  <div style="background: #ffffff; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 1px solid var(--slate-200); width: 100%; max-width: 620px; padding: 2.5rem;">
    
    <div style="text-align: center; margin-bottom: 2rem;">
      <div class="brand-badge" style="display: inline-block; margin-bottom: 0.75rem; font-size: 1.25rem;">UIU</div>
      <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--slate-900); margin-bottom: 0.25rem;">Create Your Account</h1>
      <p style="font-size: 0.875rem; color: var(--slate-500);">Join United International University official recruitment ecosystem</p>
    </div>

    <!-- Tab Selector -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; background-color: var(--slate-100); padding: 0.35rem; border-radius: var(--radius-md);">
      <button type="button" class="btn <?= ($account_type === 'student') ? 'btn-primary' : 'btn-outline' ?>" style="flex: 1; border: none;" onclick="setAccountType('student')">
        🎓 Student Registration
      </button>
      <button type="button" class="btn <?= ($account_type === 'company') ? 'btn-primary' : 'btn-outline' ?>" style="flex: 1; border: none;" onclick="setAccountType('company')">
        🏢 Corporate Recruiter
      </button>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
        <div style="margin-top: 0.5rem;">
          <a href="<?= $app_root ?>/login.php" class="btn btn-primary btn-sm">Proceed to Login &rarr;</a>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= $app_root ?>/register.php?type=<?= htmlspecialchars($account_type) ?>">
      <input type="hidden" name="account_type" id="account_type" value="<?= htmlspecialchars($account_type) ?>">

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="name@domain.edu.bd" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
        </div>
      </div>

      <!-- Student Fields -->
      <div id="student-fields" style="<?= ($account_type === 'student') ? '' : 'display: none;' ?>">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Mugdha Rahman" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Student ID *</label>
            <input type="text" name="student_id" class="form-control" placeholder="e.g. 011201049" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Department *</label>
            <select name="department" class="form-control">
              <option value="CSE">Computer Science & Engineering (CSE)</option>
              <option value="EEE">Electrical & Electronic Engineering (EEE)</option>
              <option value="BBA">Business Administration (BBA)</option>
              <option value="Data Science">Data Science & Analytics</option>
              <option value="Civil">Civil Engineering</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Batch *</label>
            <input type="text" name="batch" class="form-control" placeholder="e.g. CSE-49" value="<?= htmlspecialchars($_POST['batch'] ?? '') ?>">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">CGPA *</label>
            <input type="number" step="0.01" min="0" max="4.00" name="cgpa" class="form-control" placeholder="3.75" value="<?= htmlspecialchars($_POST['cgpa'] ?? '3.75') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Contact Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="+880 1712-345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Company Fields -->
      <div id="company-fields" style="<?= ($account_type === 'company') ? '' : 'display: none;' ?>">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Company / Organization Name *</label>
            <input type="text" name="company_name" class="form-control" placeholder="e.g. TechFlow Solutions" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Industry Sector *</label>
            <input type="text" name="sector" class="form-control" placeholder="e.g. Software & IT, Telecom" value="<?= htmlspecialchars($_POST['sector'] ?? '') ?>">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Office Location *</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Banani, Dhaka" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Official Website</label>
            <input type="url" name="website" class="form-control" placeholder="https://example.com" value="<?= htmlspecialchars($_POST['website'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Company Overview</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description of products, services, and engineering domains."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1rem; margin-top: 1rem;">
        Complete Registration &rarr;
      </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--slate-600);">
      Already have an account? <a href="<?= $app_root ?>/login.php" style="color: var(--primary); font-weight: 800; text-decoration: none;">Sign In</a>
    </div>

  </div>
</div>

<script>
function setAccountType(type) {
  document.getElementById('account_type').value = type;
  document.getElementById('student-fields').style.display = (type === 'student') ? 'block' : 'none';
  document.getElementById('company-fields').style.display = (type === 'company') ? 'block' : 'none';
  window.location.search = '?type=' + type;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
