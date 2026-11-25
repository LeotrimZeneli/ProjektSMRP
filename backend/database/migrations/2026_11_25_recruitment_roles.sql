USE smrp_db;

-- Upgrade existing installations with the hiring-manager and application-reviewer roles.
ALTER TABLE users
    MODIFY role ENUM('admin', 'hr', 'manager', 'reviewer', 'applicant') NOT NULL DEFAULT 'applicant';
