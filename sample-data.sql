-- =============================================
-- SAMPLE DATA FOR WHISTLEGUARD PLATFORM
-- =============================================
-- This file contains sample data for testing purposes only
-- No admin or super admin accounts are created
-- =============================================

USE whistleblower;

-- =============================================
-- 1. INSERT ADDITIONAL CATEGORIES
-- =============================================
INSERT INTO categories (name, description, is_active, created_at) VALUES
('Financial Mismanagement', 'Improper handling of funds, budget irregularities, wasteful spending', 1, NOW()),
('Workplace Discrimination', 'Age, gender, race, or religious discrimination in the workplace', 1, NOW()),
('Environmental Violations', 'Pollution, illegal dumping, environmental regulation violations', 1, NOW()),
('Healthcare Fraud', 'Medical billing fraud, insurance fraud, patient care violations', 1, NOW()),
('Academic Misconduct', 'Research fraud, grade tampering, degree fraud', 1, NOW()),
('IT Security Breach', 'Unauthorized system access, data leaks, cybersecurity incidents', 1, NOW()),
('Sexual Misconduct', 'Sexual harassment, assault, inappropriate behavior', 1, NOW()),
('Whistleblower Retaliation', 'Retaliation against whistleblowers who reported misconduct', 1, NOW());

-- =============================================
-- 2. INSERT SAMPLE REPORTS (Public & Approved for Dashboard)
-- =============================================

-- Report 1: Government Corruption
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_001_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
    'WHISTLE123ABC',
    (SELECT id FROM categories WHERE name = 'Corruption'),
    'IyMjIExhcmdlLVNjYWxlIENvcnJ1cHRpb24gU2NoZW1lCgpUaGlzIHJlcG9ydCBkZXRhaWxzIGEgc3lzdGVtYXRpYyBjb3JydXB0aW9uIHNjaGVtZSBpbnZvbHZpbmcgbXVsdGlwbGUgZ292ZXJubWVudCBvZmZpY2lhbHMuCgpLZXkgRmluZGluZ3M6Ci0gQXBwcm94aW1hdGVseSAkMi41TSBpbiBraWNrYmFja3MgcGFpZCBvdmVyIDIgeWVhcnMKLSBGYWtlIGludm9pY2VzIGZyb20gZmFrZSBjb21wYW5pZXMKLSBNb25leSBsYXVuZGVyZWQgdGhyb3VnaCBzaGVsbCBjb21wYW5pZXMKCkV2aWRlbmNlIGluY2x1ZGVzIGJhbmsgc3RhdGVtZW50cyBhbmQgZW1haWwgY29tbXVuaWNhdGlvbnMu',
    'investigating',
    'critical',
    'public',
    'approved',
    '2024-01-15 10:30:00'
);

-- Report 2: Healthcare Fraud
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_002_b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7',
    'WHISTLE456DEF',
    (SELECT id FROM categories WHERE name = 'Healthcare Fraud'),
    'IyMjIE1lZGljYWwgQmlsbGluZyBGcmF1ZAoKQSByZXBvcnQgYWJvdXQgYSBsYXJnZSBoZWFsdGhjYXJlIHByb3ZpZGVyIGJpbGxpbmcgTWVkaWNhcmUgZm9yIHNlcnZpY2VzIG5ldmVyIHJlbmRlcmVkLgoKRGV0YWlsczoKLSBDaGFyZ2VkIGZvciAzLDAwMCBwYXRpZW50cyB0aGF0IHdlcmUgbmV2ZXIgdHJlYXRlZAotIEZha2UgZG9jdG9yIHNpZ25hdHVyZXMgb24gY2xhaW1zCi0gT3ZlcmJpbGxpbmcgdG90YWxpbmcgJDEuMk0KCkV2aWRlbmNlIGluY2x1ZGVzIGludGVybmFsIGF1ZGl0IHJlcG9ydHMgYW5kIGVtYWlscy4=',
    'resolved',
    'high',
    'public',
    'approved',
    '2024-01-20 14:15:00'
);

-- Report 3: Workplace Harassment
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_003_c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8',
    'WHISTLE789GHI',
    (SELECT id FROM categories WHERE name = 'Harassment'),
    'IyMjIFdvcmtwbGFjZSBIYXJhc3NtZW50IENhc2UKClRoaXMgcmVwb3J0IGRldGFpbHMgaW5zdGFuY2VzIG9mIHNleHVhbCBoYXJhc3NtZW50IGJ5IGEgc2VuaW9yIG1hbmFnZXIuCgpJbmNpZGVudHM6Ci0gVW53YW50ZWQgcGh5c2ljYWwgY29udGFjdAotIEludGFwcHJvcHJpYXRlIGNvbW1lbnRzCi0gUmV0YWxpYXRpb24gZm9yIHJlamVjdGluZyBhZHZhbmNlcwoKVmljdGltcyBoYXZlIGRvY3VtZW50ZWQgYWxsIGluY2lkZW50cyB3aXRoIHRpbWVzdGFtcHMgYW5kIHdpdG5lc3Nlcy4=',
    'investigating',
    'high',
    'public',
    'approved',
    '2024-02-01 09:45:00'
);

-- Report 4: Data Breach
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_004_d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9',
    'WHISTLE012JKL',
    (SELECT id FROM categories WHERE name = 'Data Breach'),
    'IyMjIEN1c3RvbWVyIERhdGEgQnJlYWNoCgpBIHJlcG9ydCBhYm91dCBhIG1ham9yIGRhdGEgYnJlYWNoIGFmZmVjdGluZyA1MCswMDAgY3VzdG9tZXJzLgoKRGV0YWlsczoKLSBQZXJzb25hbCBpbmZvcm1hdGlvbiBsZWFrZWQgKFRFUiwgU1NOLCBjcmVkaXQgY2FyZHMpCi0gQ29tcGFueSBkZWxheWVkIHJlcG9ydGluZyBmb3IgNiBtb250aHMKLSBObyBub3RpZmljYXRpb24gc2VudCB0byBjdXN0b21lcnMKCkV2aWRlbmNlIGluY2x1ZGVzIGFjY2VzcyBsb2dzIGFuZCBpbnRlcm5hbCBtZW1vcy4=',
    'resolved',
    'critical',
    'public',
    'approved',
    '2024-02-10 11:20:00'
);

-- Report 5: Environmental Violation
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_005_e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0',
    'WHISTLE345MNO',
    (SELECT id FROM categories WHERE name = 'Safety Violations'),
    'IyMjIElsbGVnYWwgV2FzdGUgRHVtcGluZwoKQSByZXBvcnQgYWJvdXQgYW4gaW5kdXN0cmlhbCBwbGFudCBkdW1waW5nIHRveGljIHdhc3RlIGludG8gYSBsb2NhbCByaXZlci4KCkZpbmRpbmdzOgotIFRveGljIGNoZW1pY2FscyBkZXRlY3RlZCBpbiB3YXRlciBzYW1wbGVzCi0gUGxhbnQgb3BlcmF0aW5nIHdpdGhvdXQgcHJvcGVyIHBlcm1pdHMKLSBNdWx0aXBsZSB2aW9sYXRpb25zIG92ZXIgMyB5ZWFycwoKRXZpZGVuY2UgaW5jbHVkZXMgdGVzdCByZXN1bHRzIGFuZCBwaG90b3Mu',
    'investigating',
    'high',
    'public',
    'approved',
    '2024-02-15 08:30:00'
);

-- Report 6: Financial Fraud (Private - Not Public)
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_006_f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1',
    'WHISTLE678PQR',
    (SELECT id FROM categories WHERE name = 'Fraud'),
    'IyMjIEludGVybmFsIEZpbmFuY2lhbCBGcmF1ZAoKVGhpcyByZXBvcnQgZGV0YWlscyBhbiBpbnRlcm5hbCBlbXBsb3llZSB3aG8gaGFzIGJlZW4gZW1iZXp6bGluZyBjb21wYW55IGZ1bmRzLgoKRGV0YWlsczoKLSBFbXBsb3llZSBjcmVhdGVkIGZha2UgdmVuZG9yIGFjY291bnRzCi0gRW1iZXp6bGVkIG92ZXIgJDEwMCwwMDAgb3ZlciAxOCBtb250aHMKLSBVc2VkIGNvbXBhbnkgY3JlZGl0IGNhcmQgZm9yIHBlcnNvbmFsIGV4cGVuc2VzCgpFdmlkZW5jZSBpbmNsdWRlcyBmaW5hbmNpYWwgcmVjb3JkcyBhbmQgdmlkZW8gZnVvdGFnZS4=',
    'investigating',
    'medium',
    'private',
    'pending',
    '2024-02-20 13:45:00'
);

-- Report 7: Sexual Misconduct (Public)
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_007_g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2',
    'WHISTLE901STU',
    (SELECT id FROM categories WHERE name = 'Sexual Misconduct'),
    'IyMjIFNleHVhbCBNaXNjb25kdWN0IENhc2UKClRoaXMgcmVwb3J0IGRldGFpbHMgbXVsdGlwbGUgaW5jaWRlbnRzIG9mIHNleHVhbCBtaXNjb25kdWN0IGJ5IGEgc2VuaW9yIGV4ZWN1dGl2ZS4KCkluY2lkZW50czoKLSBVbndhbnRlZCBhZHZhbmNlcwotIEluYXBwcm9wcmlhdGUgdGV4dCBtZXNzYWdlcwotIFRocmVhdHMgaWYgdmljdGltcyBzcGVhayBvdXQKClNldmVyYWwgZm9ybWVyIGVtcGxveWVlcyBoYXZlIGNvbWUgZm9yd2FyZCB3aXRoIHNpbWlsYXIgYWNjb3VudHMu',
    'investigating',
    'critical',
    'public',
    'approved',
    '2024-02-25 10:00:00'
);

-- Report 8: Academic Misconduct
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_008_h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3',
    'WHISTLE234VWX',
    (SELECT id FROM categories WHERE name = 'Academic Misconduct'),
    'IyMjIFJlc2VhcmNoIEZyYXVkCgpBIHVuaXZlcnNpdHkgcHJvZmVzc29yIGhhcyBiZWVuIGZhbHNpZnlpbmcgcmVzZWFyY2ggZGF0YSBmb3IgeWVhcnMuCgpGaW5kaW5nczoKLSBEYXRhIGluIHB1Ymxpc2hlZCBwYXBlcnMgZG9lcyBub3QgbWF0Y2ggb3JpZ2luYWwgcmVzZWFyY2gKLSBJbWFnZXMgd2VyZSBkaWdpdGFsbHkgYWx0ZXJlZAotIE11bHRpcGxlIHJldHJhY3Rpb25zIG9mIHB1Ymxpc2hlZCB3b3JrCgpFdmlkZW5jZSBpbmNsdWRlcyBvcmlnaW5hbCByYXcgZGF0YSBhbmQgcHJvb2Ygb2YgYWx0ZXJhdGlvbi4=',
    'resolved',
    'medium',
    'public',
    'approved',
    '2024-03-01 14:30:00'
);

-- Report 9: IT Security Breach
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_009_i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4',
    'WHISTLE567YZA',
    (SELECT id FROM categories WHERE name = 'IT Security Breach'),
    'IyMjIFN5c3RlbSBIYWNrIFJlcG9ydAoKQSBjeWJlcmF0dGFjayBjb21wcm9taXNlZCB0aGUgY29tcGFueSdzIHNlY3VyaXR5IHN5c3RlbSByZXN1bHRpbmcgaW4gZGF0YSB0aGVmdC4KCkRldGFpbHM6Ci0gVW5rbm93biBhY2Nlc3MgZ2FpbmVkIHRocm91Z2ggcGhpc2hpbmcgZW1haWwKLSBTZW5zaXRpdmUgY29ycG9yYXRlIGRhdGEgYWNjZXNzZWQKLSBXZWFrIHBhc3N3b3JkIHBvbGljeSBlbmFibGVkIHRoZSBicmVhY2gKCkNvbXBhbnkgZGlkIG5vdCBkaXNjbG9zZSB0aGUgYnJlYWNoIHRvIGN1c3RvbWVycy4=',
    'investigating',
    'high',
    'public',
    'approved',
    '2024-03-05 09:15:00'
);

-- Report 10: Whistleblower Retaliation
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'anon_session_010_j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5',
    'WHISTLE890BCD',
    (SELECT id FROM categories WHERE name = 'Whistleblower Retaliation'),
    'IyMjIFdoaXN0bGVibG93ZXIgUmV0YWxpYXRpb24KClRoaXMgcmVwb3J0IGRldGFpbHMgaG93IGFuIGVtcGxveWVlIHdhcyBmaXJlZCBhZnRlciByZXBvcnRpbmcgdW5zYWZlIHdvcmtpbmcgY29uZGl0aW9ucy4KCkluY2lkZW50czoKLSBSZXBvcnRlZCBzYWZldHkgdmlvbGF0aW9ucyB0byBIVEkKLSBUZXJtaW5hdGVkIDMgd2Vla3MgYWZ0ZXIgcmVwb3J0Ci0gQ29tcGFueSBjbGFpbXMgImxhenkgb2ZmIHJlYXNvbnMiIGZvciBmaXJpbmcKClRoaXMgY2FzZSBpcyBjdXJyZW50bHkgd2l0aCB0aGUgTGFib3IgQm9hcmQu',
    'resolved',
    'high',
    'public',
    'approved',
    '2024-03-10 16:00:00'
);

-- =============================================
-- 3. INSERT SAMPLE EVIDENCES
-- =============================================

-- Evidence for Report 1 (Corruption)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(1, 'enc_path_corruption_bank_statements.enc', 'enc_bank_statements.enc', 'application/pdf', 245760, 15, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW()),
(1, 'enc_path_corruption_emails.enc', 'enc_email_evidence.enc', 'application/pdf', 189440, 12, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 2 (Healthcare Fraud)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(2, 'enc_path_healthcare_audit.enc', 'enc_audit_report.enc', 'application/pdf', 524288, 8, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW()),
(2, 'enc_path_healthcare_emails.enc', 'enc_emails.enc', 'application/pdf', 156672, 6, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 3 (Harassment)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(3, 'enc_path_harassment_logs.enc', 'enc_timeline_logs.enc', 'application/pdf', 98304, 25, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 4 (Data Breach)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(4, 'enc_path_breach_logs.enc', 'enc_access_logs.enc', 'application/pdf', 356352, 20, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 5 (Environmental)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(5, 'enc_path_water_tests.enc', 'enc_water_samples.enc', 'application/pdf', 232448, 10, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW()),
(5, 'enc_path_photos.enc', 'enc_photos.enc', 'image/jpeg', 12582912, 18, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 7 (Sexual Misconduct)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(7, 'enc_path_messages.enc', 'enc_chat_messages.enc', 'application/pdf', 132096, 30, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 8 (Academic Misconduct)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(8, 'enc_path_research_data.enc', 'enc_raw_data.enc', 'application/pdf', 4194304, 14, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- =============================================
-- 4. INSERT SAMPLE MESSAGES
-- =============================================

-- Messages for Report 1
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(1, 'whistleblower', 'enc_QXJlIHRoZXJlIGFueSB1cGRhdGVzIG9uIHRoZSBpbnZlc3RpZ2F0aW9uPw==', 1, '2024-01-16 09:30:00'),
(1, 'admin', 'enc_We have started reviewing the evidence. Thank you for your report.', 1, '2024-01-16 14:20:00'),
(1, 'whistleblower', 'enc_I have additional documents to share.', 1, '2024-01-17 11:00:00'),
(1, 'admin', 'enc_Please upload them through the evidence section.', 1, '2024-01-17 15:45:00');

-- Messages for Report 2
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(2, 'whistleblower', 'enc_The audit report has been sent to your email.', 1, '2024-01-21 10:15:00'),
(2, 'admin', 'enc_Received. We are launching a formal investigation.', 1, '2024-01-22 09:00:00');

-- Messages for Report 3
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(3, 'whistleblower', 'enc_I am afraid of retaliation if I come forward publicly.', 1, '2024-02-02 13:30:00'),
(3, 'admin', 'enc_We protect whistleblower identities. You are safe.', 1, '2024-02-03 10:00:00'),
(3, 'whistleblower', 'enc_Thank you. I will provide more details.', 0, '2024-02-05 16:20:00');

-- Messages for Report 7
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(7, 'whistleblower', 'enc_I have evidence of multiple incidents spanning 2 years.', 1, '2024-02-26 08:45:00'),
(7, 'admin', 'enc_This is serious. We are escalating to HR and legal.', 1, '2024-02-26 12:30:00'),
(7, 'whistleblower', 'enc_Thank you for taking this seriously.', 0, '2024-02-27 09:15:00');

-- =============================================
-- 5. INSERT ANONYMOUS SESSIONS FOR TRACKING
-- =============================================
INSERT INTO anonymous_sessions (session_id, ip_hash, user_agent_hash, report_count, last_activity, created_at) 
VALUES 
('anon_session_001_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6', 'hash_ip_session_001', 'hash_ua_session_001', 1, NOW(), '2024-01-15 10:30:00'),
('anon_session_002_b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7', 'hash_ip_session_002', 'hash_ua_session_002', 1, NOW(), '2024-01-20 14:15:00'),
('anon_session_003_c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8', 'hash_ip_session_003', 'hash_ua_session_003', 1, NOW(), '2024-02-01 09:45:00'),
('anon_session_004_d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9', 'hash_ip_session_004', 'hash_ua_session_004', 1, NOW(), '2024-02-10 11:20:00'),
('anon_session_005_e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0', 'hash_ip_session_005', 'hash_ua_session_005', 1, NOW(), '2024-02-15 08:30:00'),
('anon_session_007_g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2', 'hash_ip_session_007', 'hash_ua_session_007', 1, NOW(), '2024-02-25 10:00:00'),
('anon_session_008_h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3', 'hash_ip_session_008', 'hash_ua_session_008', 1, NOW(), '2024-03-01 14:30:00'),
('anon_session_009_i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4', 'hash_ip_session_009', 'hash_ua_session_009', 1, NOW(), '2024-03-05 09:15:00'),
('anon_session_010_j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5', 'hash_ip_session_010', 'hash_ua_session_010', 1, NOW(), '2024-03-10 16:00:00');

-- =============================================
-- 6. INSERT AUDIT LOGS (For admin activity tracking)
-- =============================================
-- Note: These are sample audit logs without admin_user_id since no admin exists yet
-- They demonstrate the structure for when admins are added
INSERT INTO audit_logs (report_id, action, ip_hash, user_agent_hash, metadata, created_at) 
VALUES 
(1, 'report_viewed', 'hash_sample_ip_001', 'hash_sample_ua_001', '{"tracking_code":"WHISTLE123ABC"}', '2024-01-16 14:20:00'),
(2, 'report_viewed', 'hash_sample_ip_002', 'hash_sample_ua_002', '{"tracking_code":"WHISTLE456DEF"}', '2024-01-22 09:00:00'),
(3, 'report_viewed', 'hash_sample_ip_003', 'hash_sample_ua_003', '{"tracking_code":"WHISTLE789GHI"}', '2024-02-03 10:00:00'),
(7, 'report_viewed', 'hash_sample_ip_007', 'hash_sample_ua_007', '{"tracking_code":"WHISTLE901STU"}', '2024-02-26 12:30:00'),
(1, 'status_updated', 'hash_sample_ip_001', 'hash_sample_ua_001', '{"old_status":"new","new_status":"investigating"}', '2024-01-20 11:00:00'),
(2, 'status_updated', 'hash_sample_ip_002', 'hash_sample_ua_002', '{"old_status":"new","new_status":"resolved"}', '2024-02-01 09:00:00');

-- =============================================
-- 7. SUMMARY OF INSERTED DATA
-- =============================================
SELECT 'Data Import Complete!' as Status;
SELECT 
    (SELECT COUNT(*) FROM categories) as Categories,
    (SELECT COUNT(*) FROM reports) as Reports,
    (SELECT COUNT(*) FROM evidences) as Evidences,
    (SELECT COUNT(*) FROM messages) as Messages,
    (SELECT COUNT(*) FROM anonymous_sessions) as Anonymous_Sessions,
    (SELECT COUNT(*) FROM audit_logs) as Audit_Logs;

-- =============================================
-- END OF SAMPLE DATA
-- =============================================