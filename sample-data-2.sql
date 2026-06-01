-- =============================================
-- SAMPLE DATA V2 FOR WHISTLEGUARD PLATFORM
-- =============================================
-- This file contains completely different sample data
-- No admin or super admin accounts are created
-- =============================================

USE whistleblower;

-- =============================================
-- 1. INSERT ADDITIONAL CATEGORIES
-- =============================================
INSERT INTO categories (name, description, is_active, created_at) VALUES
('Procurement Fraud', 'Bid rigging, vendor kickbacks, supply chain irregularities', 1, NOW()),
('Patient Abuse', 'Mistreatment of patients in healthcare facilities', 1, NOW()),
('Tax Evasion', 'Illegal tax avoidance, offshore accounts, unreported income', 1, NOW()),
('Construction Violations', 'Building code violations, unsafe construction practices', 1, NOW()),
('Police Misconduct', 'Excessive force, false arrests, evidence tampering', 1, NOW()),
('Banking Fraud', 'Money laundering, wire fraud, mortgage fraud', 1, NOW()),
('Food Safety', 'Contaminated products, unsanitary conditions, mislabeling', 1, NOW()),
('Election Interference', 'Voter suppression, ballot tampering, campaign finance violations', 1, NOW()),
('Intellectual Property Theft', 'Patent infringement, trade secret theft, copyright violations', 1, NOW()),
('Human Trafficking', 'Forced labor, exploitation, trafficking rings', 1, NOW());

-- =============================================
-- 2. INSERT SAMPLE REPORTS (Public & Approved)
-- =============================================

-- Report 1: Police Misconduct
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_001_xyz789abc',
    'WTD-24A1-9F3E',
    (SELECT id FROM categories WHERE name = 'Police Misconduct'),
    'BASE64_ENCODED_PLACEHOLDER_C1:VGhpcyByZXBvcnQgZG9jdW1lbnRzIG11bHRpcGxlIGluc3RhbmNlcyBvZiBleGNlc3NpdmUgZm9yY2UgdXNlIGJ5IG9mZmljZXJzIGluIHRoZSBNZXRyb3BvbGl0YW4gRGVwYXJ0bWVudCBiZXR3ZWVuIEphbnVhcnkgYW5kIEp1bmUgMjAyMy4gVGhlIGluY2lkZW50cyBpbmNsdWRlIHVubmVjZXNzYXJ5IHRhc2VyIGFwcGxpY2F0aW9ucyBhbmQgYXNzYXVsdHMgb24gdW5hcm1lZCBjaXZpY2lhbnMuIEJvZHkgY2FtZXJhIGZvb3RhZ2UgYW5kIG1lZGljYWwgcmVjb3JkcyBjb25maXJtIHRoZSBleGNlc3NpdmUgZm9yY2UgdXNlZC4gRm91ciBvZmZpY2VycyBoYXZlIGJlZW4gaWRlbnRpZmllZCwgaW5jbHVkaW5nIGEgbGlldXRlbmFudCBhbmQgYSBzZXJnZWFudC4g',
    'investigating',
    'critical',
    'public',
    'approved',
    '2024-06-10 14:30:00'
);

-- Report 2: Food Safety Violation
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_002_def456ghi',
    'WTD-24B2-7A1C',
    (SELECT id FROM categories WHERE name = 'Food Safety'),
    'BASE64_ENCODED_PLACEHOLDER_C2:QSByZXBvcnQgZnJvbSBhIGZvcm1lciBlbXBsb3llZSBvZiBhIG1ham9yIGZvb2QgZGlzdHJpYnV0aW9uIGNlbnRlciByZXZlYWxpbmcgd2lkZXNwcmVhZCBjb250YW1pbmF0aW9uIGFuZCB1bnNhbml0YXJ5IGNvbmRpdGlvbnMuIFJvdGVudCBpbmZlc3RhdGlvbiB3YXMgZm91bmQgaW4gMTUlIG9mIHByb2R1Y3RzIHRlc3RlZC4gVGhlIGZhY2lsaXR5IGhhcyByZWNlaXZlZCBtdWx0aXBsZSB3YXJuaW5ncyBidXQgY29udGludWVkIG9wZXJhdGlvbnMuIERvY3VtZW50ZWQgcmVjb3JkcyBzaG93IHRoYXQgbWFuYWdlbWVudCBpZ25vcmVkIGludGVybmFsIGF1ZGl0IHJlY29tbWVuZGF0aW9ucy4=',
    'investigating',
    'high',
    'public',
    'approved',
    '2024-06-15 09:45:00'
);

-- Report 3: Banking Fraud
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_003_jkl789mno',
    'WTD-24C3-8B2D',
    (SELECT id FROM categories WHERE name = 'Banking Fraud'),
    'BASE64_ENCODED_PLACEHOLDER_C3:QW4gaW5zaWRlciBhY2NvdW50YW50IHJlcG9ydHMgYSBtb25leSBsYXVuZGVyaW5nIHNjaGVtZSBpbnZvbHZpbmcgbXVsdGlwbGUgZnJvbnQgY29tcGFuaWVzIHRocm91Z2ggYSByZWdpb25hbCBiYW5rLiBBcHByb3hpbWF0ZWx5ICQ1IE0gaGFzIGJlZW4gbGF1bmRlcmVkIG92ZXIgMTggbW9udGhzIHRocm91Z2ggZmFrZSBidXNpbmVzcyBsb2Fucy4gU2V2ZXJhbCBiYW5rIGVtcGxveWVlcyBhcmUgaW52b2x2ZWQsIGluY2x1ZGluZyBhIHZpY2UgcHJlc2lkZW50LiBUcmFuc2FjdGlvbiByZWNvcmRzIGFuZCBpbnRlcm5hbCBtZW1vcyBwcm92aWRlIGV2aWRlbmNlLg==',
    'resolved',
    'critical',
    'public',
    'approved',
    '2024-06-20 11:00:00'
);

-- Report 4: Patient Abuse
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_004_pqr123stu',
    'WTD-24D4-5C3E',
    (SELECT id FROM categories WHERE name = 'Patient Abuse'),
    'BASE64_ENCODED_PLACEHOLDER_C4:QSBudXJzZSBhbmQgbGVnYWwgYWlkZSBhdCBhbiBlbGRlcmx5IGNhcmUgZmFjaWxpdHkgcmVwb3J0cyBzeXN0ZW1hdGljIG5lZ2xlY3QgYW5kIGFidXNlIG9mIHJlc2lkZW50cyBvdmVyIGEgdHdvLXllYXIgcGVyaW9kLiBSZXNpZGVudHMgd2VyZSBsZWZ0IGluIHNvaWxlZCBicmVkaW5nIGZvciBob3VycywgZGVuaWVkIG1lZGljYXRpb24sIGFuZCBzdWZmZXJlZCB1bnRyZWF0ZWQgYmVkIHNvcmVzLiBUaHJlZSByZXNpZGVudHMgZGlldCBkdWUgdG8gbmVnbGVjdC4gSW50ZXJuYWwgaW52ZXN0aWdhdGlvbiB3YXMgaGlkZGVuIGZyb20gcmVndWxhdG9ycy4=',
    'investigating',
    'critical',
    'public',
    'approved',
    '2024-06-25 13:15:00'
);

-- Report 5: Tax Evasion
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_005_vwx456yz',
    'WTD-24E5-2D8F',
    (SELECT id FROM categories WHERE name = 'Tax Evasion'),
    'BASE64_ENCODED_PLACEHOLDER_C1:QSBsYXJnZSB0ZWNoIGNvbXBhbnkgaGFzIGJlZW4gaGlkaW5nIHByb2ZpdHMgdGhyb3VnaCBvZmZzaG9yZSBhY2NvdW50cyBpbiB0YXggaGF2ZW5zIGZvciB0aGUgcGFzdCBmaXZlIHllYXJzLiBBY2NvcmRpbmcgdG8gaW50ZXJuYWwgZmluYW5jaWFsIGRvY3VtZW50cywgdGhleSBhdm9pZGVkIGFwcHJveGltYXRlbHkgJDE1IE0gaW4gZmVkZXJhbCB0YXhlcy4gVGhlIGNvbXBhbnkgdXNlZCBhIGNvbXBsZXggd2ViIG9mIHNoZWxsIGNvbXBhbmllcyBpbiB0aGUgQ2F5bWFuIElzbGFuZHMgYW5kIEx1eGVtYm91cmcuIFRocmVlIHNlbmlvciBmaW5hbmNlIGV4ZWN1dGl2ZXMgYXJlIG5hbWVkIGluIHRoZSByZXBvcnQu',
    'resolved',
    'high',
    'public',
    'approved',
    '2024-07-01 10:30:00'
);

-- Report 6: Construction Violations
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_006_abc123def',
    'WTD-24F6-7G3H',
    (SELECT id FROM categories WHERE name = 'Construction Violations'),
    'BASE64_ENCODED_PLACEHOLDER_C2:QSB3aGlzdGxlYmxvd2VyIGZyb20gYSBtYWpvciBjb25zdHJ1Y3Rpb24gZmlybSBleHBvc2VzIHN5c3RlbWF0aWMgY29kZSB2aW9sYXRpb25zIG9uIGEgbGFyZ2UgcmVzaWRlbnRpYWwgcHJvamVjdC4gU2FmZXR5IGluc3BlY3Rpb25zIHdlcmUgZmFsc2lmaWVkLCBzdWJzdGFuZGFyZCBtYXRlcmlhbHMgd2VyZSB1c2VkLCBhbmQgd29ya2VycyB3ZXJlIHB1dCBhdCByaXNrIG9mIGluanVyeS4gQSBwYXJ0aWFsIGNvbGxhcHNlIG9jY3VycmVkIGluIEZlYnJ1YXJ5IDIwMjQsIGluanVyaW5nIHRocmVlIHdvcmtlcnMuIEludGVybmFsIGVtYWlscyBzaG93IGtub3dsZWRnZSBvZiB0aGUgdmlvbGF0aW9ucyBieSBtYW5hZ2VtZW50Lg==',
    'investigating',
    'high',
    'public',
    'approved',
    '2024-07-05 08:00:00'
);

-- Report 7: Procurement Fraud
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_007_ghi456jkl',
    'WTD-24G7-8H4I',
    (SELECT id FROM categories WHERE name = 'Procurement Fraud'),
    'BASE64_ENCODED_PLACEHOLDER_C3:QSBjb250cmFjdG9yIGZvciBhIGZlZGVyYWwgYWdlbmN5IHJlcG9ydHMgYmlkIHJpZ2dpbmcgYW5kIGtpY2tiYWNrcyBpbiBhICQ1MCBNIGRlZmVuc2UgY29udHJhY3QuIFRoZSBjb21wYW55IHRoYXQgd29uIHRoZSBiaWQgaXMgY29ubmVjdGVkIHRvIGEgZ292ZXJubWVudCBvZmZpY2lhbCB3aG8gb3ZlcnNhdyB0aGUgY29udHJhY3QuIFBheW1lbnRzIG9mICQyIE0gd2VyZSBtYWRlIHRvIGEgc2hlbGwgY29tcGFueSBvd25lZCBieSB0aGUgb2ZmaWNpYWwncyBicm90aGVyLWluLWxhdy4gRW1haWxzIGFuZCBmaW5hbmNpYWwgcmVjb3JkcyBkb2N1bWVudCB0aGUgZnJhdWQu',
    'investigating',
    'critical',
    'public',
    'approved',
    '2024-07-10 15:45:00'
);

-- Report 8: Human Trafficking
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_008_mno789pqr',
    'WTD-24H8-9J5K',
    (SELECT id FROM categories WHERE name = 'Human Trafficking'),
    'BASE64_ENCODED_PLACEHOLDER_C4:QSByZXBvcnQgZnJvbSBhbiB1bmRlcmNvdmVyIGludmVzdGlnYXRvciByZXZlYWxzIGEgaHVtYW4gdHJhZmZpa2luZyByaW5nIG9wZXJhdGluZyB1bmRlciB0aGUgZ3Vpc2Ugb2YgYSBsZWdpdGltYXRlIGJ1c2luZXNzLiBBdCBsZWFzdCA1MCB2aWN0aW1zIGZyb20gU291dGhlYXN0IEFzaWEgd2VyZSBicm91Z2h0IGlsbGVnYWxseSBhbmQgZm9yY2VkIHRvIHdvcmsgaW4gc3dlYXRzaG9wcyB1bmRlciB0aHJlYXQgb2YgZGVwb3J0YXRpb24uIFRoZSBvcGVyYXRpb24gaGFzIGJlZW4gcnVubmluZyBmb3IgdGhyZWUgeWVhcnMuIFZpY3RpbSB0ZXN0aW1vbmllcyBhbmQgZmluYW5jaWFsIHJlY29yZHMgYXJlIGluY2x1ZGVkLg==',
    'investigating',
    'critical',
    'private',
    'pending',
    '2024-07-15 12:00:00'
);

-- Report 9: Intellectual Property Theft
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_009_stu123vwx',
    'WTD-24I9-1K6L',
    (SELECT id FROM categories WHERE name = 'Intellectual Property Theft'),
    'BASE64_ENCODED_PLACEHOLDER_C1:QSBmb3JtZXIgcmVzZWFyY2ggYW5kIGRldmVsb3BtZW50IGRpcmVjdG9yIGF0IGEgc3RhcnR1cCBjb21wYW55IHN0b2xlIHRyYWRlIHNlY3JldHMgYW5kIHNvbGQgdGhlbSB0byBhIGNvbXBldGl0b3IuIFRoZSBzdG9sZW4gdGVjaG5vbG9neSBpbmNsdWRlZCBwcm9wcmlldGFyeSBhbGdvcml0aG1zIGFuZCBjdXN0b21lciBkYXRhYmFzZXMuIFRoZSBjb21wZXRpdG9yIGxhdW5jaGVkIGEgc2ltaWxhciBwcm9kdWN0IGZpdmUgbW9udGhzIGxhdGVyLCBjYXVzaW5nIHRoZSBzdGFydHVwIHRvIGxvc2UgYW4gZXN0aW1hdGVkICQ4IE0gaW4gcmV2ZW51ZS4gRW1haWxzIGFuZCBmaWxlIHRyYW5zZmVyIGxvZ3MgcHJvdmlkZSBldmlkZW5jZS4=',
    'resolved',
    'high',
    'public',
    'approved',
    '2024-07-20 09:30:00'
);

-- Report 10: Election Interference
INSERT INTO reports (anonymous_session_id, tracking_code, category_id, encrypted_description, status, priority, visibility, publication_status, created_at) 
VALUES (
    'whistle_2024_010_yzab456cd',
    'WTD-24J0-2L7M',
    (SELECT id FROM categories WHERE name = 'Election Interference'),
    'BASE64_ENCODED_PLACEHOLDER_C2:QSBjYW1wYWlnbiB3b3JrZXIgcmVwb3J0cyBzeXN0ZW1hdGljIHZvdGVyIHN1cHByZXNzaW9uIHRhY3RpY3MgaW4gdGhyZWUgY291bnRpZXMgZHVyaW5nIHRoZSBwcmltYXJ5IGVsZWN0aW9uLiBSZXNpZGVudHMgZnJvbSBtaW5vcml0eSBuZWlnaGJvcmhvb2RzIHdlcmUgdHVybmVkIGF3YXkgZnJvbSBwb2xsaW5nIHN0YXRpb25zLCBhbmQgYmFsbG90cyB3ZXJlIGRpc2NhcmRlZCBkdWUgdG8gImNvbnNpc3RlbmN5IGlzc3Vlcy4iIEludGVybmFsIGNvbW11bmljYXRpb25zIHNob3cgY29vcmRpbmF0ZWQgZWZmb3J0cyB0byBzdXBwcmVzcyB2b3RlcyBpbiBwcmVkb21pbmFudGx5IE1pbm9yaXR5LWhlbHZpbmcgZGlzdHJpY3RzLiAg',
    'investigating',
    'high',
    'public',
    'approved',
    '2024-07-25 16:15:00'
);

-- =============================================
-- 3. INSERT SAMPLE EVIDENCES
-- =============================================

-- Evidence for Report 1 (Police Misconduct)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(1, 'enc_path_police_bodycam.enc', 'bodycam_footage_23_03_15.enc', 'video/mp4', 15728640, 45, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW()),
(1, 'enc_path_police_medical.enc', 'medical_reports_victims.enc', 'application/pdf', 524288, 28, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW()),
(1, 'enc_path_police_internal.enc', 'internal_investigation.enc', 'application/pdf', 8912896, 12, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 2 (Food Safety)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(2, 'enc_path_food_test.enc', 'lab_test_results.enc', 'application/pdf', 2359296, 34, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW()),
(2, 'enc_path_food_photos.enc', 'facility_photos.enc', 'image/jpeg', 18874368, 52, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW());

-- Evidence for Report 3 (Banking Fraud)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(3, 'enc_path_bank_transactions.enc', 'transaction_records.enc', 'application/pdf', 4194304, 67, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW()),
(3, 'enc_path_bank_emails.enc', 'internal_emails.enc', 'application/pdf', 1572864, 41, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW());

-- Evidence for Report 4 (Patient Abuse)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(4, 'enc_path_nursing_logs.enc', 'care_logs.enc', 'application/pdf', 3145728, 89, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW()),
(4, 'enc_path_nursing_photos.enc', 'conditions_photos.enc', 'image/jpeg', 12582912, 76, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW());

-- Evidence for Report 5 (Tax Evasion)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(5, 'enc_path_tax_docs.enc', 'offshore_accounts.enc', 'application/pdf', 6291456, 23, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW()),
(5, 'enc_path_tax_financial.enc', 'financial_statements.enc', 'application/pdf', 9437184, 18, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW());

-- Evidence for Report 6 (Construction Violations)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(6, 'enc_path_const_inspections.enc', 'falsified_inspections.enc', 'application/pdf', 2097152, 34, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW()),
(6, 'enc_path_const_photos.enc', 'site_photos.enc', 'image/jpeg', 14680064, 29, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW()),
(6, 'enc_path_const_emails.enc', 'management_emails.enc', 'application/pdf', 1048576, 15, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 7 (Procurement Fraud)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(7, 'enc_path_procurement_contracts.enc', 'contract_documents.enc', 'application/pdf', 3670016, 42, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW()),
(7, 'enc_path_procurement_emails.enc', 'email_exchanges.enc', 'application/pdf', 1835008, 31, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW());

-- Evidence for Report 8 (Human Trafficking)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(8, 'enc_path_trafficking_statements.enc', 'victim_statements.enc', 'application/pdf', 4718592, 56, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW()),
(8, 'enc_path_trafficking_financial.enc', 'financial_trails.enc', 'application/pdf', 2621440, 38, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());

-- Evidence for Report 9 (IP Theft)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(9, 'enc_path_ip_transfers.enc', 'file_transfer_logs.enc', 'application/pdf', 1310720, 27, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW()),
(9, 'enc_path_ip_emails.enc', 'communication_logs.enc', 'application/pdf', 786432, 19, 1, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW());

-- Evidence for Report 10 (Election Interference)
INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, view_count, is_public, expires_at, created_at) 
VALUES 
(10, 'enc_path_election_voter.enc', 'voter_suppression_docs.enc', 'application/pdf', 3145728, 73, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW()),
(10, 'enc_path_election_communications.enc', 'internal_comms.enc', 'application/pdf', 2097152, 58, 1, DATE_ADD(NOW(), INTERVAL 60 DAY), NOW());

-- =============================================
-- 4. INSERT SAMPLE MESSAGES
-- =============================================

-- Messages for Report 1 (Police Misconduct)
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(1, 'whistleblower', 'enc_SmVzd2VyLCBJIGhhdmUgYWRkaXRpb25hbCBib2R5IGNhbWVyYSBmb290YWdlIGZyb20gYXJlc3RzLiBIb3cgZG8gSSBzdWJtaXQgaXQ/', 1, '2024-06-11 09:15:00'),
(1, 'admin', 'enc_VGhvdXNhbmRzIHRoYW5rIHlvdS4gUGxlYXNlIHVwbG9hZCB0aGUgZmlsZXMgdGhyb3VnaCB0aGUgZXZpZGVuY2Ugc2VjdGlvbi4=', 1, '2024-06-11 14:30:00'),
(1, 'whistleblower', 'enc_VGhlIHZpZGVvIGZpbGVzIGFyZSBsYXJnZS4gSXMgdGhlcmUgYW4gZW1haWwgYWRkcmVzcyBpIGNhbiBzZW5kIHRoZW0gdG8/', 1, '2024-06-12 10:45:00'),
(1, 'admin', 'enc_WW91IGNhbiB1c2UgdGhlIGZpbGUgdXBsb2FkIGZlYXR1cmUuIEl0IGFjY2VwdHMgdXAgdG8gMjUwTUIu', 1, '2024-06-12 16:20:00'),
(1, 'whistleblower', 'enc_VGhhbmsgeW91LiBJJ2xsIHRyeSB0byBjb21wcmVzcyB0aGUgZmlsZXMgZmlyc3Qu', 0, '2024-06-13 08:30:00');

-- Messages for Report 2 (Food Safety)
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(2, 'whistleblower', 'enc_SG93IGxvbmcgd2lsbCB0aGUgaW52ZXN0aWdhdGlvbiB0YWtlPyBUaGUgZmFjaWxpdHkgaXMgc3RpbGwgb3BlcmF0aW5nLg==', 1, '2024-06-16 11:30:00'),
(2, 'admin', 'enc_V2UncmUgd29ya2luZyB3aXRoIHRoZSBEQkguIFRoZSBmYWNpbGl0eSB3aWxsIGJlIGluc3BlY3RlZCB3aXRoaW4gNyBkYXlzLg==', 1, '2024-06-17 09:00:00');

-- Messages for Report 3 (Banking Fraud)
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(3, 'whistleblower', 'enc_SSBhbSBjb25jZXJuZWQgYWJvdXQgbXkgc2FmZXR5IGlmIEknbSBkaXNjb3ZlcmVkLg==', 1, '2024-06-21 13:00:00'),
(3, 'admin', 'enc_WW91ciBpZGVudGl0eSBpcyBmdWxseSBwcm90ZWN0ZWQuIFdlIHRha2UgcmV0YWxpYXRpb24gdmVyeSBzZXJpb3VzbHku', 1, '2024-06-21 17:45:00'),
(3, 'admin', 'enc_VGhlIEZCSSBoYXMgYmVlbiBub3RpZmllZC4gVGhhbmsgeW91IGZvciB5b3VyIGNvdXJhZ2Uu', 1, '2024-06-25 10:15:00');

-- Messages for Report 4 (Patient Abuse)
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(4, 'whistleblower', 'enc_VGhlcmUgYXJlIG1vcmUgcmVzaWRlbnRzIHdobyBoYXZlIHNpbWlsYXIgc3Rvcmllcy4gQ2FuIEkgcHV0IHRoZW0gaW4gdG91Y2g/', 1, '2024-06-26 14:30:00'),
(4, 'admin', 'enc_VGhlIG1vcmUgZXZpZGVuY2UgdGhlIGJldHRlci4gUGxlYXNlIGFzayB0aGVtIHRvIGNvbnRhY3QgdXMgaW5kZXBlbmRlbnRseS4=', 1, '2024-06-27 09:45:00'),
(4, 'whistleblower', 'enc_SSd2ZSBzcG9rZW4gdG8gZm91ciBvdGhlciBmYW1pbGllcy4gVGhleSBhcmUgYWZyYWlkIHRvIGNvbWUgZm9yd2FyZC4=', 0, '2024-06-28 11:20:00');

-- Messages for Report 6 (Construction Violations)
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(6, 'whistleblower', 'enc_VGhlIGNvbXBhbnkgaXMgdHJ5aW5nIHRvIGludGltaWRhdGUgd29ya2Vycy4gV2hhdCBzaG91bGQgSSBkbz8=', 1, '2024-07-06 07:45:00'),
(6, 'admin', 'enc_S2VlcCBkb2N1bWVudGluZyBldmVyeXRoaW5nLiBZb3VyIHNhZmV0eSBpcyBvdXIgcHJpb3JpdHku', 1, '2024-07-06 13:30:00'),
(6, 'whistleblower', 'enc_VGhleSBmaXJlZCB0d28gb3RoZXIgd2hpc3RlYmxvd2VycyBhbHJlYWR5LiBJIGZlZWwgdGhyZWF0ZW5lZC4=', 0, '2024-07-07 10:00:00');

-- Messages for Report 9 (IP Theft)
INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, created_at) 
VALUES 
(9, 'whistleblower', 'enc_VGhlIGNvbXBldGl0b3IgaXMgc2VsbGluZyBvdXIgdGVjaG5vbG9neSBpbiBFdXJvcGUuIEEgY29sbGVhZ3VlIGZvdW5kIGl0Lg==', 1, '2024-07-21 10:00:00'),
(9, 'admin', 'enc_VGhhdCdzIGltcG9ydGFudCBldmlkZW5jZS4gV2Ugd2lsbCBub3RpZnkgb3VyIGxlZ2FsIHRlYW0gaW1tZWRpYXRlbHku', 1, '2024-07-21 15:30:00'),
(9, 'admin', 'enc_VGhlIGxhd3N1aXQgaGFzIGJlZW4gZmlsZWQuIFlvdXIgY29udHJpYnV0aW9uIG1hZGUgYSBkaWZmZXJlbmNlLg==', 1, '2024-08-01 09:00:00'),
(9, 'whistleblower', 'enc_VGhhbmsgeW91IGZvciBwcm90ZWN0aW5nIG1lLg==', 0, '2024-08-02 16:45:00');

-- =============================================
-- 5. INSERT ANONYMOUS SESSIONS
-- =============================================
INSERT INTO anonymous_sessions (session_id, ip_hash, user_agent_hash, report_count, last_activity, created_at) 
VALUES 
('whistle_2024_001_xyz789abc', 'hash_police_misconduct_001', 'ua_misconduct_001', 1, DATE_ADD(NOW(), INTERVAL 5 DAY), '2024-06-10 14:30:00'),
('whistle_2024_002_def456ghi', 'hash_food_safety_002', 'ua_food_002', 1, DATE_ADD(NOW(), INTERVAL 10 DAY), '2024-06-15 09:45:00'),
('whistle_2024_003_jkl789mno', 'hash_banking_fraud_003', 'ua_banking_003', 1, DATE_ADD(NOW(), INTERVAL 15 DAY), '2024-06-20 11:00:00'),
('whistle_2024_004_pqr123stu', 'hash_patient_abuse_004', 'ua_patient_004', 1, DATE_ADD(NOW(), INTERVAL 20 DAY), '2024-06-25 13:15:00'),
('whistle_2024_005_vwx456yz', 'hash_tax_evasion_005', 'ua_tax_005', 1, DATE_ADD(NOW(), INTERVAL 25 DAY), '2024-07-01 10:30:00'),
('whistle_2024_006_abc123def', 'hash_construction_006', 'ua_const_006', 1, DATE_ADD(NOW(), INTERVAL 30 DAY), '2024-07-05 08:00:00'),
('whistle_2024_007_ghi456jkl', 'hash_procurement_007', 'ua_procure_007', 1, DATE_ADD(NOW(), INTERVAL 35 DAY), '2024-07-10 15:45:00'),
('whistle_2024_008_mno789pqr', 'hash_trafficking_008', 'ua_traffick_008', 1, DATE_ADD(NOW(), INTERVAL 40 DAY), '2024-07-15 12:00:00'),
('whistle_2024_009_stu123vwx', 'hash_ip_theft_009', 'ua_ip_009', 1, DATE_ADD(NOW(), INTERVAL 45 DAY), '2024-07-20 09:30:00'),
('whistle_2024_010_yzab456cd', 'hash_election_010', 'ua_election_010', 1, DATE_ADD(NOW(), INTERVAL 50 DAY), '2024-07-25 16:15:00');

-- =============================================
-- 6. INSERT AUDIT LOGS
-- =============================================
INSERT INTO audit_logs (report_id, action, ip_hash, user_agent_hash, metadata, created_at) 
VALUES 
(1, 'report_viewed', 'hash_audit_ip_001', 'hash_audit_ua_001', '{"tracking_code":"WTD-24A1-9F3E"}', '2024-06-12 14:30:00'),
(2, 'report_viewed', 'hash_audit_ip_002', 'hash_audit_ua_002', '{"tracking_code":"WTD-24B2-7A1C"}', '2024-06-17 09:45:00'),
(3, 'report_viewed', 'hash_audit_ip_003', 'hash_audit_ua_003', '{"tracking_code":"WTD-24C3-8B2D"}', '2024-06-22 11:15:00'),
(4, 'report_viewed', 'hash_audit_ip_004', 'hash_audit_ua_004', '{"tracking_code":"WTD-24D4-5C3E"}', '2024-06-27 14:30:00'),
(5, 'report_viewed', 'hash_audit_ip_005', 'hash_audit_ua_005', '{"tracking_code":"WTD-24E5-2D8F"}', '2024-07-03 10:00:00'),
(6, 'report_viewed', 'hash_audit_ip_006', 'hash_audit_ua_006', '{"tracking_code":"WTD-24F6-7G3H"}', '2024-07-08 13:15:00'),
(7, 'report_viewed', 'hash_audit_ip_007', 'hash_audit_ua_007', '{"tracking_code":"WTD-24G7-8H4I"}', '2024-07-12 16:00:00'),
(1, 'status_updated', 'hash_audit_ip_001', 'hash_audit_ua_001', '{"old_status":"new","new_status":"investigating"}', '2024-06-15 09:30:00'),
(3, 'status_updated', 'hash_audit_ip_003', 'hash_audit_ua_003', '{"old_status":"new","new_status":"resolved"}', '2024-06-28 14:45:00'),
(5, 'status_updated', 'hash_audit_ip_005', 'hash_audit_ua_005', '{"old_status":"new","new_status":"resolved"}', '2024-07-10 11:30:00'),
(9, 'status_updated', 'hash_audit_ip_009', 'hash_audit_ua_009', '{"old_status":"new","new_status":"resolved"}', '2024-07-28 10:15:00'),
(4, 'status_updated', 'hash_audit_ip_004', 'hash_audit_ua_004', '{"old_status":"investigating","new_status":"investigating"}', '2024-07-05 09:00:00');

-- =============================================
-- 7. SUMMARY
-- =============================================
SELECT '=== SAMPLE DATA V2 IMPORT COMPLETE ===' as Status;
SELECT 
    'Categories' as Item,
    (SELECT COUNT(*) FROM categories) as Count
UNION ALL
SELECT 'Reports', (SELECT COUNT(*) FROM reports)
UNION ALL
SELECT 'Evidences', (SELECT COUNT(*) FROM evidences)
UNION ALL
SELECT 'Messages', (SELECT COUNT(*) FROM messages)
UNION ALL
SELECT 'Anonymous Sessions', (SELECT COUNT(*) FROM anonymous_sessions)
UNION ALL
SELECT 'Audit Logs', (SELECT COUNT(*) FROM audit_logs);

-- =============================================
-- TRACKING CODES REFERENCE
-- =============================================
SELECT '=== TRACKING CODES FOR TESTING ===' as Info;
SELECT 'WTD-24A1-9F3E' as 'Tracking Code', 'Police Misconduct' as Report
UNION ALL SELECT 'WTD-24B2-7A1C', 'Food Safety Violation'
UNION ALL SELECT 'WTD-24C3-8B2D', 'Banking Fraud'
UNION ALL SELECT 'WTD-24D4-5C3E', 'Patient Abuse'
UNION ALL SELECT 'WTD-24E5-2D8F', 'Tax Evasion'
UNION ALL SELECT 'WTD-24F6-7G3H', 'Construction Violations'
UNION ALL SELECT 'WTD-24G7-8H4I', 'Procurement Fraud'
UNION ALL SELECT 'WTD-24I9-1K6L', 'Intellectual Property Theft'
UNION ALL SELECT 'WTD-24J0-2L7M', 'Election Interference';

-- =============================================
-- END OF SAMPLE DATA V2
-- =============================================