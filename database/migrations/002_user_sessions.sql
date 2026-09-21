-- Session + interaction logging (§5) — supports server-side session management, user
-- activity tracking, and audit of authenticated interaction across the system.
CREATE TABLE IF NOT EXISTS user_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id VARCHAR(190) NOT NULL,
  user_id BIGINT UNSIGNED,
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(500),
  path VARCHAR(500) NOT NULL,
  method VARCHAR(10) NOT NULL,
  query_string VARCHAR(1000) DEFAULT NULL,
  referer VARCHAR(500) DEFAULT NULL,
  response_status SMALLINT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_session (session_id, user_id),
  KEY idx_user (user_id, last_activity),
  CONSTRAINT fk_user_session FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-request interaction log for authenticated users (traceable audit trail).
CREATE TABLE IF NOT EXISTS user_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED,
  session_id VARCHAR(190) NOT NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(60) DEFAULT NULL,
  entity_id BIGINT UNSIGNED DEFAULT NULL,
  request_path VARCHAR(500) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_action (user_id, created_at),
  KEY idx_entity (entity_type, entity_id),
  CONSTRAINT fk_user_action FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;