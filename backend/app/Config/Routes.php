<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Set the default namespace
$routes->setDefaultNamespace('App\Controllers');

// Enable Auto Route
$routes->setAutoRoute(false);

// 404 Override
// $routes->set404Override();

// The Auto Routing (Legacy) is very dangerous. It is easy to accidentally permit access to areas of your application that you did not intend to allow access to. Use the defined routes below instead of relying on auto routing.

/**
 * ===================================
 * Health Check Route (Public)
 * ===================================
 */
$routes->get('health', 'Health::check', ['namespace' => 'App\Controllers']);

/**
 * ===================================
 * Authentication Routes (Public + Protected)
 * ===================================
 */
$routes->group('auth', static function ($routes) {
    // Public endpoints (rate-limited: 10 req/min)
    $routes->post('sso-login', 'Auth::ssoLogin', ['filter' => 'ratelimit:10,60']);
    $routes->post('login',     'Auth::login',    ['filter' => 'ratelimit:10,60']);
    $routes->post('refresh',       'Auth::refresh',      ['filter' => 'ratelimit:10,60']);
    $routes->post('service-login', 'Auth::serviceLogin', ['filter' => 'ratelimit:10,60']);

    // Protected endpoints
    $routes->get('verify', 'Auth::verify', ['filter' => 'permission']);
    $routes->post('logout', 'Auth::logout', ['filter' => 'permission']);
});

/**
 * ===================================
 * Profile Module Routes
 * ===================================
 */
$routes->group('profile', ['filter' => 'permission'], static function ($routes) {
    // Personal Profile
    $routes->get('/', 'Profile::myProfile');
    $routes->get('(:num)', 'Profile::getProfile/$1');
    $routes->put('/', 'Profile::updateProfile');
    
    // Personal Details
    $routes->get('personal-details', 'Profile::getPersonalDetails');
    $routes->put('personal-details', 'Profile::updatePersonalDetails');
    
    // Addresses
    $routes->get('addresses', 'Profile::getAddresses');
    $routes->post('addresses', 'Profile::addAddress');
    $routes->put('addresses/(:num)', 'Profile::updateAddress/$1');
    $routes->delete('addresses/(:num)', 'Profile::deleteAddress/$1');
    
    // Emergency Contacts
    $routes->get('emergency-contacts', 'Profile::getEmergencyContacts');
    $routes->post('emergency-contacts', 'Profile::addEmergencyContact');
    $routes->put('emergency-contacts/(:num)', 'Profile::updateEmergencyContact/$1');
    
    // Govt IDs
    $routes->get('govt-ids', 'Profile::getGovtIds');
    $routes->post('govt-ids', 'Profile::addGovtId');
    $routes->put('govt-ids/(:num)', 'Profile::updateGovtId/$1');
    
    // Bank Details
    $routes->get('bank-details', 'Profile::getBankDetails');
    $routes->post('bank-details', 'Profile::addBankDetail');
    $routes->put('bank-details/(:num)', 'Profile::updateBankDetail/$1');
    
    // Health Records
    $routes->get('health', 'Profile::getHealthRecords');
    $routes->put('health', 'Profile::updateHealthRecords');
    
    // Family Dependents
    $routes->get('family-dependents', 'Profile::getFamilyDependents');
    $routes->get('family-dependents/(:num)', 'Profile::getFamilyDependent/$1');
    $routes->post('family-dependents', 'Profile::addFamilyDependent');
    $routes->put('family-dependents/(:num)', 'Profile::updateFamilyDependent/$1');
    $routes->delete('family-dependents/(:num)', 'Profile::deleteFamilyDependent/$1');

    // Languages
    $routes->get('languages', 'Profile::getLanguages');
    $routes->get('languages/(:num)', 'Profile::getLanguage/$1');
    $routes->post('languages', 'Profile::addLanguage');
    $routes->put('languages/(:num)', 'Profile::updateLanguage/$1');
    $routes->delete('languages/(:num)', 'Profile::deleteLanguage/$1');

    // Hobbies / Sports / Talents
    $routes->get('hobbies', 'Profile::getHobbies');
    $routes->get('hobbies/(:num)', 'Profile::getHobby/$1');
    $routes->post('hobbies', 'Profile::addHobby');
    $routes->put('hobbies/(:num)', 'Profile::updateHobby/$1');
    $routes->delete('hobbies/(:num)', 'Profile::deleteHobby/$1');

    // Volunteer Activities
    $routes->get('volunteer-activities', 'Profile::getVolunteerActivities');
    $routes->get('volunteer-activities/(:num)', 'Profile::getVolunteerActivity/$1');
    $routes->post('volunteer-activities', 'Profile::addVolunteerActivity');
    $routes->put('volunteer-activities/(:num)', 'Profile::updateVolunteerActivity/$1');
    $routes->delete('volunteer-activities/(:num)', 'Profile::deleteVolunteerActivity/$1');

    // Patents
    $routes->get('patents', 'Profile::getPatents');
    $routes->get('patents/(:num)', 'Profile::getPatent/$1');
    $routes->post('patents', 'Profile::addPatent');
    $routes->put('patents/(:num)', 'Profile::updatePatent/$1');
    $routes->delete('patents/(:num)', 'Profile::deletePatent/$1');

    // Physical Location
    $routes->get('physical-location', 'Profile::getPhysicalLocation');
    $routes->put('physical-location', 'Profile::updatePhysicalLocation');

    // Working Conditions
    $routes->get('working-conditions', 'Profile::getWorkingConditions');
    $routes->put('working-conditions', 'Profile::updateWorkingConditions');

    // Mobility Preferences
    $routes->get('mobility-preferences', 'Profile::getMobilityPreferences');
    $routes->put('mobility-preferences', 'Profile::updateMobilityPreferences');

    // GDPR Consents
    $routes->get('consents', 'Profile::getConsents');
    $routes->post('consents', 'Profile::recordConsent');
    $routes->delete('consents', 'Profile::withdrawAllConsents');

    // Data Version History
    $routes->get('versions', 'Profile::getVersionHistory');
});

/**
 * ===================================
 * Job & Organization Module Routes
 * ===================================
 */
$routes->group('job', ['filter' => 'permission'], static function ($routes) {
    // Current Job Information
    $routes->get('information', 'Job::getJobInformation');
    $routes->get('information/(:num)', 'Job::getJobInformationById/$1');
    $routes->put('information', 'Job::updateJobInformation');
    
    // Employment History
    $routes->get('history', 'Job::getEmploymentHistory');
    $routes->get('history/(:num)', 'Job::getEmploymentHistoryId/$1');
    $routes->post('history', 'Job::addEmploymentHistory');
    
    // Organization Hierarchy
    $routes->get('org-hierarchy', 'Job::getOrgHierarchy');
    $routes->get('org-hierarchy/(:num)', 'Job::getOrgHierarchyId/$1');
    $routes->get('team-members', 'Job::getTeamMembers');
    $routes->get('reporting-structure', 'Job::getReportingStructure');
    
    // Promotions
    $routes->get('promotions', 'Job::getPromotions');
    $routes->post('promotions', 'Job::createPromotion');
    $routes->put('promotions/(:num)', 'Job::updatePromotion/$1');
    $routes->delete('promotions/(:num)', 'Job::deletePromotion/$1');

    // Transfers
    $routes->get('transfers', 'Job::getTransfers');
    $routes->post('transfers', 'Job::createTransfer');
    $routes->put('transfers/(:num)', 'Job::updateTransfer/$1');
    $routes->delete('transfers/(:num)', 'Job::deleteTransfer/$1');
});

/**
 * ===================================
 * Performance Module Routes
 * ===================================
 */
$routes->group('performance', ['filter' => 'permission'], static function ($routes) {
    // Performance Reviews
    $routes->get('reviews', 'Performance::getReviews');
    $routes->get('reviews/(:num)', 'Performance::getReviewId/$1');
    $routes->post('reviews', 'Performance::createReview');
    $routes->put('reviews/(:num)', 'Performance::updateReview/$1');
    
    // Performance Goals
    $routes->get('goals', 'Performance::getGoals');
    $routes->get('goals/(:num)', 'Performance::getGoalId/$1');
    $routes->post('goals', 'Performance::createGoal');
    $routes->put('goals/(:num)', 'Performance::updateGoal/$1');
    $routes->delete('goals/(:num)', 'Performance::deleteGoal/$1');

    // Performance Feedback
    $routes->get('feedback', 'Performance::getFeedback');
    $routes->get('feedback/(:num)', 'Performance::getFeedbackId/$1');
    $routes->post('feedback', 'Performance::createFeedback');
    $routes->put('feedback/(:num)', 'Performance::updateFeedback/$1');
    $routes->delete('feedback/(:num)', 'Performance::deleteFeedback/$1');
    
    // Ratings
    $routes->get('ratings', 'Performance::getRatings');
    $routes->put('ratings/(:num)', 'Performance::updateRating/$1');
});

/**
 * ===================================
 * Talent Management Module Routes
 * ===================================
 */
$routes->group('talent', ['filter' => 'permission'], static function ($routes) {
    // Skills
    $routes->get('skills', 'Talent::getSkills');
    $routes->get('skills/(:num)', 'Talent::getSkillId/$1');
    $routes->post('skills', 'Talent::addSkill');
    $routes->put('skills/(:num)', 'Talent::updateSkill/$1');
    
    // Competencies
    $routes->get('competencies', 'Talent::getCompetencies');
    $routes->get('competencies/(:num)', 'Talent::getCompetencyId/$1');
    
    // Employee Competencies
    $routes->get('my-competencies', 'Talent::getMyCompetencies');
    $routes->put('my-competencies/(:num)', 'Talent::updateMyCompetency/$1');
    
    // Certifications
    $routes->get('certifications', 'Talent::getCertifications');
    $routes->get('certifications/(:num)', 'Talent::getCertificationId/$1');
    $routes->post('certifications', 'Talent::addCertification');
    $routes->put('certifications/(:num)', 'Talent::updateCertification/$1');
    
    // Individual Development Plan
    $routes->get('idp/all', 'Talent::getIdpAll');
    $routes->get('idp', 'Talent::getIdp');
    $routes->post('idp', 'Talent::createIdp');
    $routes->put('idp/(:num)', 'Talent::updateIdp/$1');
    
    // Succession Plans
    $routes->get('succession', 'Talent::getSuccessionPlans');
    $routes->get('succession/(:num)', 'Talent::getSuccessionPlan/$1');
    $routes->post('succession', 'Talent::createSuccessionPlan');
    $routes->put('succession/(:num)', 'Talent::updateSuccessionPlan/$1');
    $routes->delete('succession/(:num)', 'Talent::deleteSuccessionPlan/$1');

    // Awards
    $routes->get('awards', 'Talent::getAwards');
    $routes->get('awards/(:num)', 'Talent::getAwardId/$1');

    // Career Paths
    $routes->get('career-paths', 'Talent::getCareerPaths');
    $routes->post('career-paths', 'Talent::createCareerPath');
    $routes->put('career-paths/(:num)', 'Talent::updateCareerPath/$1');
    $routes->delete('career-paths/(:num)', 'Talent::deleteCareerPath/$1');
});

/**
 * ===================================
 * Learning & Development Module Routes
 * ===================================
 */
$routes->group('learning', ['filter' => 'permission'], static function ($routes) {
    // Courses
    $routes->get('courses', 'Learning::getCourses');
    $routes->get('courses/(:num)', 'Learning::getCourseId/$1');
    
    // Course Enrollments
    $routes->get('enrollments', 'Learning::getEnrollments');
    $routes->get('enrollments/(:num)', 'Learning::getEnrollmentId/$1');
    $routes->post('enrollments', 'Learning::createEnrollment');
    $routes->put('enrollments/(:num)', 'Learning::updateEnrollment/$1');
    
    // Training History
    $routes->get('training-history', 'Learning::getTrainingHistory');
    $routes->get('training-history/(:num)', 'Learning::getTrainingHistoryId/$1');
    $routes->post('training-history', 'Learning::createTrainingHistory');
    $routes->put('training-history/(:num)', 'Learning::updateTrainingHistory/$1');
    $routes->delete('training-history/(:num)', 'Learning::deleteTrainingHistory/$1');
    
    // Learning Paths
    $routes->get('learning-paths', 'Learning::getLearningPaths');

    // Mentoring Programs
    $routes->get('mentoring', 'Learning::getMentoring');
    $routes->post('mentoring', 'Learning::createMentoring');
    $routes->put('mentoring/(:num)', 'Learning::updateMentoring/$1');
    $routes->delete('mentoring/(:num)', 'Learning::deleteMentoring/$1');

    // Skills Gap Analysis
    $routes->get('skills-gap', 'Learning::getSkillsGap');
    $routes->post('skills-gap', 'Learning::createSkillsGap');
    $routes->put('skills-gap/(:num)', 'Learning::updateSkillsGap/$1');
    $routes->delete('skills-gap/(:num)', 'Learning::deleteSkillsGap/$1');
});

/**
 * ===================================
 * Compliance Module Routes
 * ===================================
 */
$routes->group('compliance', ['filter' => 'permission'], static function ($routes) {
    // Compliance Documents
    $routes->get('documents', 'Compliance::getDocuments');
    $routes->get('documents/(:num)', 'Compliance::getDocumentId/$1');
    $routes->post('documents', 'Compliance::uploadDocument');
    $routes->put('documents/(:num)', 'Compliance::updateDocument/$1');
    
    // Document Status
    $routes->get('document-status', 'Compliance::getDocumentStatus');
    $routes->put('documents/(:num)/sign', 'Compliance::signDocument/$1');
});

/**
 * ===================================
 * Admin/HR Routes
 * ===================================
 */
$routes->group('admin', ['filter' => 'permission'], static function ($routes) {
    // Employee Management
    $routes->get('employees', 'Admin::listEmployees');
    $routes->post('employees', 'Admin::createEmployee');
    $routes->put('employees/(:num)', 'Admin::updateEmployee/$1');
    $routes->delete('employees/(:num)', 'Admin::deleteEmployee/$1');
    
    // User Management
    $routes->get('users', 'Admin::listUsers');
    $routes->post('users', 'Admin::createUser');
    $routes->put('users/(:num)', 'Admin::updateUser/$1');
    $routes->get('users/hrms-compare', 'Admin::usersHrmsCompare');

    // Sync Management (reads from HRMS DB directly)
    $routes->post('sync/employees', 'Admin::syncEmployees');
    $routes->get('sync/status', 'Admin::getSyncStatus');
    $routes->get('sync/logs', 'Admin::getSyncLogs');
    
    // Audit Logs (enhanced: search, filter, export)
    $routes->get('audit-logs', 'Admin::getAuditLogs');
    $routes->get('audit-logs/export', 'Admin::exportAuditLogs');
    $routes->get('audit-logs/(:num)', 'Admin::getAuditLogId/$1');

    // System Configuration
    $routes->get('configuration', 'Admin::getConfiguration');
    $routes->put('configuration/(:segment)', 'Admin::updateConfiguration/$1');
});

/**
 * ===================================
 * Admin/HR Analytics Routes
 * ===================================
 */
$routes->group('analytics', ['filter' => 'permission'], static function ($routes) {
    // Organizational Analytics
    $routes->get('org-structure', 'Analytics::getOrgStructure');
    $routes->get('department-stats', 'Analytics::getDepartmentStats');
    $routes->get('team-stats', 'Analytics::getTeamStats');
    
    // Performance Analytics
    $routes->get('performance-summary', 'Analytics::getPerformanceSummary');
    $routes->get('review-statistics', 'Analytics::getReviewStatistics');
    
    // Talent Analytics
    $routes->get('skill-inventory', 'Analytics::getSkillInventory');
    $routes->get('competency-matrix', 'Analytics::getCompetencyMatrix');
    
    // Learning Analytics
    $routes->get('training-stats', 'Analytics::getTrainingStats');
    $routes->get('course-effectiveness', 'Analytics::getCourseEffectiveness');
    
    // HR Metrics
    $routes->get('hr-dashboard', 'Analytics::getHrDashboard');
    $routes->get('employee-engagement', 'Analytics::getEmployeeEngagement');
});

/**
 * ===================================
 * Search Routes
 * ===================================
 */
$routes->group('search', ['filter' => 'permission'], static function ($routes) {
    $routes->get('employees', 'Search::searchEmployees');
    $routes->get('skills', 'Search::searchSkills');
    $routes->get('courses', 'Search::searchCourses');
    $routes->get('global', 'Search::globalSearch');
});

/**
 * ===================================
 * File Upload Routes
 * ===================================
 */
$routes->group('upload', ['filter' => 'permission'], static function ($routes) {
    $routes->post('profile-picture', 'Upload::uploadProfilePicture');
    $routes->post('certificate', 'Upload::uploadCertificate');
    $routes->post('document', 'Upload::uploadDocument');
    $routes->post('bulk-employees', 'Upload::bulkUploadEmployees');
});

/**
 * ===================================
 * Export Routes
 * ===================================
 */
$routes->group('export', ['filter' => 'permission'], static function ($routes) {
    $routes->get('employee-profile/(:num)', 'Export::employeeProfile/$1');
    $routes->get('org-chart', 'Export::orgChart');
    $routes->get('performance-report', 'Export::performanceReport');
    $routes->get('skill-audit', 'Export::skillAudit');
});

/**
 * ===================================
 * Report Routes
 * ===================================
 */
$routes->group('reports', ['filter' => 'permission'], static function ($routes) {
    $routes->get('employee-summary', 'Reports::employeeSummary');
    $routes->get('org-structure', 'Reports::orgStructure');
    $routes->get('performance', 'Reports::performance');
    $routes->get('training', 'Reports::training');
    $routes->get('compliance', 'Reports::compliance');
    $routes->get('headcount', 'Reports::headcount');
});

/**
 * ===================================
 * Dashboard Routes
 * ===================================
 */
$routes->group('dashboard', ['filter' => 'permission'], static function ($routes) {
    $routes->get('my-dashboard', 'Dashboard::myDashboard');
    $routes->get('manager-dashboard', 'Dashboard::managerDashboard');
    $routes->get('hr-dashboard', 'Dashboard::hrDashboard');
    $routes->get('admin-dashboard', 'Dashboard::adminDashboard');
});

/**
 * ===================================
 * API Documentation Routes (Public)
 * ===================================
 */
$routes->group('docs', static function ($routes) {
    $routes->get('/', 'Docs::index');
    $routes->get('api', 'Docs::apiDocumentation');
    $routes->get('endpoints', 'Docs::endpoints');
});

/**
 * ===================================
 * Catch-all route (404)
 * ===================================
 */
$routes->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '(:any)', 'NotFound::handle');
