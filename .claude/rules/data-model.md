## 8. Dữ liệu cốt lõi

Tối thiểu gồm:

- users, roles/permissions.
- provinces, industrial_parks.
- companies, company_locations.
- jobs.
- candidates.
- applications (bao gồm cột `assigned_to` nullable, khóa ngoại tới `users`, dùng ở cả Phase 1 và Phase 2 — xem mục 1.1).
- application_status_histories.
- application_notes.
- candidate_sources/referrers.
- favorites.
- pages, faqs, settings.
- audit_logs.

Định nghĩa model, relationship, index, foreign key và unique constraint rõ ràng.
