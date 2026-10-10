<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm ">
      <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>

    <!-- MOBILE -->
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
      <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <div>
      <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
        <!-- Nama manajer yang sedang login (dari session) -->
        <li class="ms-3 dropdown">
          <a href="#" role="button" class="d-flex align-items-center gap-2 text-reset text-decoration-none"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-user-circle fs-3"></i>
            <span class="fw-semibold"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
            <i class="ti ti-chevron-down small"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 220px;">
            <div class="border-bottom px-3 py-3">
              <h4 class="mb-0 small fw-semibold"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></h4>
              <p class="mb-0 small text-secondary"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></p>
              <span class="badge bg-primary-subtle text-primary mt-2">Manajer Operasional</span>
            </div>
            <div class="p-3 small">
              <a href="login/logout.php" class="link-danger d-flex align-items-center gap-2"
                onclick="return confirm('Yakin ingin logout?')">
                <i class="ti ti-logout"></i><span>Logout</span>
              </a>
            </div>
          </div>
        </li>
      </ul>
    </div>

  </nav>