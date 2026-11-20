CREATE TABLE IF NOT EXISTS notification_profiles (
  user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  email VARCHAR(254) NOT NULL,
  verified_at DATETIME NULL,
  last_sent_at BIGINT NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_verifications (
  token_hash CHAR(64) NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  email VARCHAR(254) NOT NULL,
  expires_at BIGINT NOT NULL,
  INDEX idx_verification_user(user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alert_rules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  client_uid VARCHAR(64) NOT NULL,
  symbol VARCHAR(8) NOT NULL,
  market ENUM('tse','otc') NOT NULL,
  config JSON NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  email_enabled BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_alert_owner(user_id,client_uid),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alert_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  rule_id BIGINT UNSIGNED NOT NULL,
  rule_version INT UNSIGNED NOT NULL,
  event_type ENUM('stop','profit','time') NOT NULL,
  content JSON NOT NULL,
  unread BOOLEAN NOT NULL DEFAULT TRUE,
  hidden BOOLEAN NOT NULL DEFAULT FALSE,
  email VARCHAR(254) NULL,
  email_state ENUM('pending','sending','sent','failed','skipped','cancelled') NOT NULL DEFAULT 'skipped',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  next_attempt BIGINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_alert_once(rule_id,rule_version,event_type),
  INDEX idx_alert_mail(email_state,next_attempt),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
