<?php
/**
 * Common Header Include
 * Requires $user and $profile variables to be set before including
 */
$userName = '';
if (!empty($profile)) {
    $userName = trim(($profile['name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
}
if (empty($userName) && !empty($user)) {
    $userName = $user['name'] ?? ($user['email'] ?? 'User');
}
$userInitials = '';
$parts = explode(' ', trim($userName));
if (count($parts) >= 2) {
    $userInitials = strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
} else {
    $userInitials = strtoupper(mb_substr($userName, 0, 2));
}
$userRole = $user['role'] ?? 'employee';

// ── Fetch role-based left menu + site settings from HRMS DB ──
if (!isset($hrmsMenuItems)) {
    $hrmsMenuItems = [];
    $siteCfg = [];
    try {
        $hrmsDb = get_hrms_db();
        $empId = get_hrms_emp_id();
        $roleStmt = $hrmsDb->prepare("SELECT role_id FROM employee WHERE empID = ? LIMIT 1");
        $roleStmt->execute([$empId]);
        $hrmsRoleId = $roleStmt->fetchColumn();
        if ($hrmsRoleId) {
            $menuStmt = $hrmsDb->prepare("
                SELECT ms.id, ms.module_name, ms.url, ms.parent_id, ms.display_order,
                       ms.external_link, ms.link_open_in,
                       si.svgi_icon_file, si.svgi_icon_svg
                FROM menu_setting ms
                LEFT JOIN svg_icons_master si ON si.svgi_icon_id = ms.iconID
                WHERE ms.role_id = ? AND ms.parent_id = 0
                ORDER BY ms.display_order ASC
            ");
            $menuStmt->execute([$hrmsRoleId]);
            $hrmsMenuItems = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // Fetch site settings (logo, favicon)
        $siteStmt = $hrmsDb->prepare("SELECT logo, favicon FROM settings LIMIT 1");
        $siteStmt->execute();
        $siteCfg = $siteStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $ex) {
        // Silently fall through
    }
}
if (!isset($hrmsLogo)) {
    $hrmsLogo   = !empty($siteCfg['logo'])    ? HRMS_BASE . '/uploads/img/logo/' . $siteCfg['logo']    : 'assets/media/logos/logo-dark.png';
}
if (!isset($hrmsFavicon)) {
    $hrmsFavicon = !empty($siteCfg['favicon']) ? HRMS_BASE . '/uploads/img/logo/' . $siteCfg['favicon'] : 'assets/media/logos/favicon.ico';
}

// Menu helpers (define once)
if (!function_exists('hrmsMenuUrl')) {
    function hrmsMenuUrl($item) {
        $url = '';
        if (!empty($item['external_link'])) {
            $url = $item['external_link'];
        } elseif (!empty($item['url']) && $item['url'] !== '#') {
            $url = HRMS_BASE . '/' . ltrim($item['url'], '/');
        }
        if (empty($url)) return 'javascript:;';
        return 'hrms_redirect.php?to=' . urlencode($url);
    }
    function hrmsRedirectUrl($path = '') {
        $url = HRMS_BASE . ($path ? '/' . ltrim($path, '/') : '');
        return 'hrms_redirect.php?to=' . urlencode($url);
    }
    function menuIcon($item) {
        if (!empty($item['svgi_icon_file'])) {
            return '<img src="' . e(HRMS_BASE . '/uploads/icons/' . $item['svgi_icon_file']) . '" alt="" style="width:22px;height:22px;opacity:.6;" />';
        }
        if (!empty($item['svgi_icon_svg'])) {
            return $item['svgi_icon_svg'];
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#a5a4b5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?= e($pageTitle ?? 'Workforce Profile') ?> | <?= SITE_TITLE ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
  <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
  <link href="assets/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/responsive.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/bn-style.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/themes/layout/header/base/light.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/themes/layout/header/menu/light.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/themes/layout/brand/light.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/themes/layout/aside/light.css" rel="stylesheet" type="text/css" />
  <link rel="shortcut icon" href="<?= e($hrmsFavicon) ?>" />
  <link rel="stylesheet" type="text/css" href="assets/css/ui-v2.css">
  <link rel="stylesheet" type="text/css" href="assets/css/workforce-profile-module.css">
</head>
<body id="kt_body" class="header-fixed aside-fixed aside-fixed-body header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">
  <!--begin::Main-->
  <!--begin::Header Mobile-->
  <div id="kt_header_mobile" class="header-mobile align-items-center header-mobile-fixed">
    <a href="index.php">
      <img alt="Logo" src="<?= e($hrmsLogo) ?>" />
    </a>
    <div class="d-flex align-items-center h-100">
      <button id="kt_aside_mobile_toggle" class="btn btn-icon btn-clean btn-lg btn-dropdown ml-3">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24" /><rect fill="#4f8aff" x="4" y="5" width="16" height="3" rx="1.5" /><path d="M5.5,15 L18.5,15 C19.3284271,15 20,15.6715729 20,16.5 C20,17.3284271 19.3284271,18 18.5,18 L5.5,18 C4.67157288,18 4,17.3284271 4,16.5 C4,15.6715729 4.67157288,15 5.5,15 Z M5.5,10 L18.5,10 C19.3284271,10 20,10.6715729 20,11.5 C20,12.3284271 19.3284271,13 18.5,13 L5.5,13 C4.67157288,13 4,12.3284271 4,11.5 C4,10.6715729 4.67157288,10 5.5,10 Z" fill="#4f8aff" opacity="0.3" /></g></svg>
      </button>
      <button class="btn btn-icon btn-clean btn-lg btn-dropdown ml-3" id="kt_header_mobile_topbar_toggle">
        <span class="svg-icon svg-icon-xl"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24" /><circle fill="#000000" cx="12" cy="5" r="2" /><circle fill="#000000" cx="12" cy="12" r="2" /><circle fill="#000000" cx="12" cy="19" r="2" /></g></svg></span>
      </button>
      <div class="topbar-item border-left ml-3 pl-4 h-100 d-flex">
        <div class="btn btn-icon btn-icon-mobile w-auto btn-clean d-flex align-items-center btn-lg px-2 align-self-center">
          <span class="symbol symbol-lg-35 symbol-25 symbol-light-success rounded-circle overflow-hidden">
            <div class="symbol-label d-flex align-items-center justify-content-center font-weight-bold"><?= e($userInitials) ?></div>
          </span>
        </div>
      </div>
    </div>
  </div>
  <!--end::Header Mobile-->
  <div class="d-flex flex-column flex-root">
    <div class="d-flex flex-row flex-column-fluid page">
      <!--begin::Aside-->
      <div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto fixedWidthAside" id="kt_aside">
        <div class="brand flex-column-auto" id="kt_brand">
          <a href="<?= e(hrmsRedirectUrl()) ?>" class="brand-logo">
            <img alt="Logo" class="w-100 px-2" src="<?= e($hrmsLogo) ?>" />
          </a>
        </div>
        <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
          <div id="kt_aside_menu" class="aside-menu my-4" data-menu-vertical="1" data-menu-scroll="1" data-menu-dropdown-timeout="500">
            <ul class="menu-nav">
              <?php if (!empty($hrmsMenuItems)): ?>
                <?php foreach ($hrmsMenuItems as $item): ?>
                  <li class="menu-item" aria-haspopup="true">
                    <a href="<?= e(hrmsMenuUrl($item)) ?>" class="menu-link">
                      <span class="svg-icon menu-icon"><?= menuIcon($item) ?></span>
                      <span class="menu-text"><?= e(trim($item['module_name'])) ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="menu-item" aria-haspopup="true">
                  <a href="<?= e(hrmsRedirectUrl()) ?>" class="menu-link">
                    <span class="svg-icon menu-icon"><?= menuIcon([]) ?></span>
                    <span class="menu-text">HR Dashboard</span>
                  </a>
                </li>
              <?php endif; ?>
              <li class="menu-item menu-item-active" aria-haspopup="true">
                <a href="index.php" class="menu-link">
                  <span class="svg-icon menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#a5a4b5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </span>
                  <span class="menu-text">Employee Profile</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!--end::Aside-->
      <div class="d-flex flex-column flex-row-fluid wrapper no-subheader" id="kt_wrapper">
        <!--begin::Header-->
        <div id="kt_header" class="header header-fixed">
          <div class="container-fluid d-flex align-items-stretch justify-content-between">
            <div class="d-inline-flex align-items-center flex-grow-1 max-w-450px">
              <div class="input-group input-group-sm flex-nowrap main-header-search">
                <div class="input-group-prepend mr-n8">
                  <span class="input-group-text bg-transparent pr-0 py-0 border-0 rounded rounded-right-0 mr-1px">
                    <i class="flaticon2-search-1 font-14 p-0"></i>
                  </span>
                </div>
                <input type="text" placeholder="Search Employees, Skills, Courses..." class="form-control h-100 bg-secondary-o-80 font-14 pl-10" autocomplete="off">
              </div>
            </div>
            <div class="topbar">
              <div class="d-flex gap-20 align-self-center">
                <a class="btn btn-xs btn-primary radius-10px d-flex align-items-center gap-5 font-13" href="<?= e(hrmsRedirectUrl()) ?>">
                  <i class="la la-video p-0 font-18 line-height-normal"></i>Go Online
                </a>
                <a href="<?= e(hrmsRedirectUrl()) ?>" class="btn btn-xs px-0 btn-outline-secondary bg-transparent border-0 d-flex align-items-center gap-5 text-hover-black font-13">
                  <i class="flaticon2-calendar-6 font-14 p-0 line-height-normal"></i>Schedule
                </a>
              </div>
              <span class="dash my-auto max-h-30px mx-2"></span>
              <div class="dropdown d-none d-lg-flex">
                <div class="topbar-item" data-toggle="dropdown" data-offset="10px,0px">
                  <div class="btn btn-icon btn-outline-secondary btn-dropdown border-0 btn-sm rounded-circle mr-1 noti-btn btn-hover-light">
                    <span class="svg-icon svg-icon-primary position-relative">
                      <span class="noti-count sm">0</span>
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell w-4 h-4"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>
                    </span>
                    <span class="pulse-ring"></span>
                  </div>
                </div>
                <div class="dropdown-menu p-0 m-0 dropdown-menu-right dropdown-menu-anim-up dropdown-menu-lg">
                  <form>
                    <div class="d-flex flex-column pt-8 pb-6 bgi-size-cover bgi-no-repeat rounded-top" style="background-image: url(<?= e(HRMS_BASE) ?>/assets/media/misc/bg-1.jpg)">
                      <h4 class="d-flex flex-center rounded-top"><span class="text-white">User Notifications</span></h4>
                    </div>
                    <div class="p-8 d-flex flex-column justify-content-center align-items-center gap-10">
                      <span class="text-muted">No Notification Found</span>
                    </div>
                  </form>
                </div>
              </div>
              <div class="topbar-item">
                <a href="<?= e(hrmsRedirectUrl()) ?>" class="btn btn-icon btn-outline-secondary btn-dropdown border-0 btn-sm rounded-circle mr-1 noti-btn btn-hover-light">
                  <span class="svg-icon svg-icon-primary position-relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </span>
                </a>
              </div>
              <span class="dash my-auto max-h-30px mx-2"></span>
              <div class="topbar-item d-none d-lg-flex">
                <div class="btn btn-icon btn-icon-mobile w-auto btn-clean d-flex align-items-center btn-sm rounded-circle px-0 ml-2" id="kt_quick_user_toggle">
                  <span class="symbol symbol-35 symbol-light-success rounded-circle overflow-hidden">
                    <div class="symbol-label d-flex align-items-center justify-content-center font-weight-bold"><?= e($userInitials) ?></div>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!--end::Header-->
