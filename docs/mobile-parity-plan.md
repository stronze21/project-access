# Bosesmoto Mobile Parity Plan

## Objective
Build a full Flutter mobile app without breaking the existing Laravel web app.

## Current Mobile API Status
Implemented and tested:
- `POST /api/mobile/auth/register`
- `POST /api/mobile/auth/login`
- `POST /api/mobile/auth/logout`
- `GET /api/mobile/me`
- `PATCH /api/mobile/me`
- `GET /api/mobile/lookups/categories`
- `GET /api/mobile/lookups/barangays`
- `GET /api/mobile/lookups/officials`
- `GET /api/mobile/complaints`
- `GET /api/mobile/complaints/{complaint}`
- `POST /api/mobile/complaints`
- `GET /api/mobile/my/complaints`
- `GET /api/mobile/my/complaints/{complaint}`
- `PUT /api/mobile/my/complaints/{complaint}`
- `POST /api/mobile/my/complaints/{complaint}/confirm-resolution`
- `POST /api/mobile/complaints/{complaint}/support`
- `POST /api/mobile/complaints/{complaint}/comments`
- `POST /api/mobile/complaints/{complaint}/comments/{comment}/react`

## What Should Be In Mobile (Recommended)

### Phase 1: Public + Citizen Core (must-have)
- Public complaint feed/list
- Public complaint details
- Login / Register / Forgot Password / Logout
- Citizen profile view/update
- Submit complaint (with validation)
- My complaints list/details
- Edit complaint (when allowed)
- Confirm resolution
- Support complaint
- Comment + react on complaint comments

### Phase 2: Engagement Features
- Polls list/details/vote
- Sentiment feed/trending
- Sentiment post create/edit/delete
- Sentiment comments/reactions/reports
- Follow/unfollow users

### Phase 3: Internal Operations (Admin/Mayor/Dept/Officer)
- Role dashboards
- Complaint management queue/details
- Assign department/officer
- Set priority/status/internal notes
- Moderate/override/official tags
- Attachments upload/download
- Audit logs
- Executive dashboard
- Monthly reports
- Reference data CRUD (categories/departments/officials)
- User management CRUD

## Web-to-Mobile Function Mapping

### Public
- `GET /complaints` -> mobile feed API exists
- `GET /complaints/{complaint}` -> mobile detail API exists
- `GET /complaints/similar` -> mobile API TODO
- `POST /complaints/anonymous` -> mobile submit API exists (anonymous path)

### Citizen
- `/my-complaints` -> mobile API TODO
- `POST /complaints` -> mobile API exists (basic), citizen-auth variant needs token flow
- `PUT /complaints/{complaint}` -> mobile API TODO
- `POST /complaints/{complaint}/confirm-resolution` -> mobile API TODO
- `POST /complaints/{complaint}/support` -> mobile API TODO
- `POST /complaints/{complaint}/comments` -> mobile API TODO
- `POST /complaints/{complaint}/comments/{comment}/react` -> mobile API TODO

### Polls + Sentiments
- Full parity TODO (Phase 2)

### Internal Management and Admin
- Full parity TODO (Phase 3)

## Architecture Rules (to keep web stable)
- Do not modify existing web route behavior.
- Add only new API routes under `/api/mobile/*`.
- Use versioning once expanding: `/api/mobile/v1/*`.
- Protect citizen/internal endpoints with Sanctum token auth.
- Keep response contracts stable after release.
- Add feature tests for every new mobile endpoint.

## Definition of "Complete"
Complete parity means all role-based web capabilities are available in mobile for:
- Public users
- Citizens
- Admin/Super Admin
- Mayor
- Department Head
- Action Officer

This is feasible, but should be delivered in phases to reduce risk.

## Recommended Next Build Step
1. Implement mobile auth token flow (Sanctum) and profile endpoints.
2. Add lookup endpoints for categories/barangays/officials.
3. Wire Flutter Submit tab to real `POST /api/mobile/complaints`.
4. Add "My Complaints" API and page.
