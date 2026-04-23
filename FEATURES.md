# AyudaHub Platform - Comprehensive Features List

> **AyudaHub** is a full-stack barangay management platform for aid/assistance (ayuda) distribution in the Philippines. It consists of two applications:
>
> - **AyudaHub (Web Admin Portal)** — Laravel 11 + Livewire 3, for barangay administrators, program managers, distribution officers, and registration officers.
> - **AyudaHubNative (Mobile Resident Portal)** — .NET 9 MAUI + Blazor, for barangay residents with Digital ID, aid tracking, and offline support.

---

## Table of Contents

1. [Technology Stack](#1-technology-stack)
2. [Authentication & Authorization](#2-authentication--authorization)
3. [Resident Management](#3-resident-management)
4. [Household Management](#4-household-management)
5. [Ayuda Programs Management](#5-ayuda-programs-management)
6. [Distribution Management](#6-distribution-management)
7. [Distribution Batch Management](#7-distribution-batch-management)
8. [QR Code & RFID Scanning](#8-qr-code--rfid-scanning)
9. [Digital ID System (Mobile)](#9-digital-id-system-mobile)
10. [Dashboard & Analytics](#10-dashboard--analytics)
11. [Reports & Data Export](#11-reports--data-export)
12. [Announcements](#12-announcements)
13. [Notifications & Push Notifications](#13-notifications--push-notifications)
14. [User & Access Management](#14-user--access-management)
15. [System Settings & Configuration](#15-system-settings--configuration)
16. [Activity Logging & Audit Trail](#16-activity-logging--audit-trail)
17. [Offline Functionality & Sync (Mobile)](#17-offline-functionality--sync-mobile)
18. [Resident Profile & Self-Service (Mobile)](#18-resident-profile--self-service-mobile)
19. [Address / Location System](#19-address--location-system)
20. [API Endpoints Summary](#20-api-endpoints-summary)

---

## 1. Technology Stack

### AyudaHub — Web Admin Portal

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| Reactive UI | Livewire 3.6 |
| Auth | Laravel Sanctum, Jetstream (2FA support) |
| Authorization | Spatie Laravel Permission (RBAC) |
| CSS | Tailwind CSS 3.4+, DaisyUI 4.12+, Mary UI |
| Build | Vite 6.0+ |
| QR Code | SimpleSoftwareIO/simple-qrcode, Bacon QR Code |
| Push Notifications | Firebase Cloud Messaging (FCM) |
| Database | SQLite / MySQL |

### AyudaHubNative — Mobile Resident Portal

| Layer | Technology |
|---|---|
| Framework | .NET 9 MAUI with Blazor WebView |
| Local DB | SQLite (offline storage) |
| UI | MudBlazor 8.0.0 (Material Design) |
| QR Code | QRCoder 1.6.0 |
| Push Notifications | Firebase Cloud Messaging (Xamarin.Firebase.Messaging) |
| Platforms | Android (API 23+), iOS (14.2+), Windows, macOS Catalyst |
| API | REST — `https://test.ayudaportal.com/api/resident-portal` |

---

## 2. Authentication & Authorization

### Web Admin Portal

| Feature | Description |
|---|---|
| User login | Email + password authentication via Jetstream |
| API tokens | Sanctum-based tokens for API access |
| Two-Factor Authentication | TOTP 2FA via Jetstream |
| Role-Based Access Control | Spatie permissions — roles: System Administrator, Program Manager, Distribution Officer, Registration Officer, Barangay Administrator, Staff |
| Granular permissions | 19+ permissions across residents, households, programs, distributions, reports, users, announcements, QR codes, settings |
| Middleware protection | Route- and component-level authorization checks |
| Password security | Bcrypt hashing, validation rules, confirmation on change |

### Mobile Resident Portal

| Feature | Description |
|---|---|
| Resident login | Sign in with Resident ID, Email, or Contact Number + password |
| Registration | Self-registration with barangay-verified Resident ID, Last Name, and Birth Date |
| 60-day session tokens | Auto-expiry with countdown display and warnings |
| Offline login | Cached session allows login without network if session is valid |
| Device tracking | Device name and platform recorded per login |
| Password change | In-app password change with confirmation |

---

## 3. Resident Management

### Web Admin (Full CRUD)

| Feature | Description |
|---|---|
| Resident registration | Complete demographic data entry with auto-generated Resident ID (`R-YYYYMM-XXXX`) |
| Profile fields | Name, suffix, birth date, birthplace, gender, civil status, contact, email, blood type, occupation, income, education, emergency contact |
| Special sector flags | PWD, Senior Citizen (auto-detected age >= 60), Solo Parent, Pregnant, Lactating, Indigenous, 4Ps Beneficiary, Registered Voter |
| Photo upload | Profile photo storage and management |
| Signature capture | Digital signature via XP-Pen tablet or canvas, with status tracking (pending/verified) |
| QR code generation | Unique QR code per resident (`QR-R-[hash]`), downloadable as PNG |
| ID card generation | Landscape and portrait orientations, single and batch generation |
| CSV import/export | Template download, bulk import with error tracking, filtered export |
| Portal account | Create password for mobile app access, track last login |
| Advanced search | Filter by barangay, special sector, status, portal account status |
| Batch operations | Batch ID card generation, batch QR code download (ZIP) |

### Mobile Resident Portal (Self-Service)

| Feature | Description |
|---|---|
| View profile | Full personal, demographic, and household information |
| Edit profile | Update contact number, email, occupation, emergency contact |
| Photo upload | Upload/change profile photo with local caching |
| Signature upload | Digital signature capture pad |
| Demographic tags | Visual badges for PWD, Senior, Solo Parent, Pregnant, 4Ps, etc. |
| QR code display | Quick-access QR code on home screen |

---

## 4. Household Management

### Web Admin (Full CRUD)

| Feature | Description |
|---|---|
| Household registration | Auto-generated Household ID (`HH-YYYYMM-XXXX`) |
| Address fields | Street, barangay, city/municipality, province, region, postal code (PSGC codes) |
| Infrastructure | Dwelling type (owned/rented/shared/informal/other), electricity, water supply |
| Financial | Monthly income auto-calculated from member incomes |
| Member management | Assign residents to households, designate head of household |
| Auto-calculations | Member count, total income auto-updated |
| QR code | Unique QR code per household |
| Search & filter | Advanced filtering and search |

### Mobile Resident Portal (View Only)

| Feature | Description |
|---|---|
| Household details | View address, member count, dwelling type, utilities |
| Household members | View all members in the household |

---

## 5. Ayuda Programs Management

### Web Admin (Full CRUD)

| Feature | Description |
|---|---|
| Program creation | Name, code (auto-generated), description, type (Cash/Goods/Services/Mixed) |
| Frequency | One-time, Weekly, Monthly, Quarterly, Annual |
| Budget tracking | Total budget, budget used, remaining budget, progress percentage |
| Beneficiary limits | Max beneficiaries, current count, remaining slots |
| Date range | Start and end dates |
| Status tracking | Upcoming, Active, Full, Budget Exhausted, Completed, Inactive |
| Verification flag | Optional verification requirement for distributions |
| **Eligibility criteria engine** | Dynamic rules per program — criterion types: age, gender, civil status, income, household income, household size, location, voter status, disability, solo parent, pregnancy, indigenous status, occupation, education |
| Comparison operators | `=`, `!=`, `>`, `<`, `>=`, `<=`, `in`, `not_in`, `contains`, `starts_with`, `ends_with`, `between` |
| Eligibility checking | Automatic eligibility validation for residents against program criteria |

### Mobile Resident Portal (View Only)

| Feature | Description |
|---|---|
| Browse programs | View available ayuda programs |
| Program details | View program type, description, dates, requirements |
| Check eligibility | Check own eligibility for a specific program |

---

## 6. Distribution Management

### Web Admin (Full CRUD)

| Feature | Description |
|---|---|
| Distribution creation | Auto-generated reference number (`D-YYYYMMDD-XXXX`) |
| Linking | Linked to program, resident, household, and batch |
| Details | Amount (cash), goods description, services description |
| Status workflow | Pending → Verified → Distributed (also: Rejected, Cancelled) |
| Verification | Verifier tracking, verification data (JSON), verification notes |
| Receipt upload | Receipt image documentation |
| Tracking | Distributed by, verified by, distribution date |
| Search & filter | Filter by program, status, date range; multi-column search |

### Mobile Resident Portal (View Only)

| Feature | Description |
|---|---|
| Distribution list | View all assigned distributions (paginated, 15/page) |
| Status tracking | See pending vs. completed distributions |
| Summary dashboard | Total distributions received, total amount, pending count |
| Summary by program | Count and total per program |
| Summary by year | Count and amount grouped by year |
| Upcoming distributions | View next scheduled batches with location and timing |
| Filter | Filter by status and program |

---

## 7. Distribution Batch Management

### Web Admin Only

| Feature | Description |
|---|---|
| Batch creation | Auto-generated batch number, program assignment |
| Scheduling | Location, date, start/end time, target beneficiary count |
| Status | Scheduled, Ongoing, Completed, Cancelled |
| Statistics | Actual vs. target beneficiaries, total amount, completion percentage |
| Barangay batch distribution | Bulk distribution by barangay with sector filters (seniors, PWD, solo parents, household heads) |
| Batch verification | QR code scanning for resident verification during distribution |
| Preview | Preview statistics before processing |
| Associated distributions | View all distributions within a batch |

---

## 8. QR Code & RFID Scanning

### Web Admin

| Feature | Description |
|---|---|
| QR generation | For residents (`QR-R-[hash]`) and households (`QR-HH-[hash]`) |
| QR download | Single and batch download (ZIP) |
| QR scanner | Real-time camera-based QR code scanning |
| RFID input | Manual RFID number entry |
| Auto-processing | Instant resident/household lookup from scanned code |
| Batch verification scanner | QR scanning during batch distribution for verification |

### Mobile Resident Portal

| Feature | Description |
|---|---|
| QR display | Resident's unique QR code on home screen for quick identification |
| QR on Digital ID | QR code embedded on back of digital ID card |
| Offline QR | Base64-encoded QR available offline |

---

## 9. Digital ID System (Mobile)

### Mobile Resident Portal Only

| Feature | Description |
|---|---|
| **Digital ID card** | Flip-able card with front and back views |
| **Front side** | Republic of the Philippines header, Barangay Resident Identification Card, photo, Resident ID, signature, full name, date of birth, address |
| **Back side** | Date of issuance, sex, marital status, place of birth, emergency contact, occupation, special sector, QR code, AyudaHub footer |
| Flip animation | Tap-to-flip between front and back |
| Offline access | ID card data cached for offline viewing |
| Retry | Reload button if generation fails |

---

## 10. Dashboard & Analytics

### Web Admin

| Feature | Description |
|---|---|
| **Summary cards** | Total distributions, total amount, unique residents reached, unique households reached |
| Distribution by program | Count and total amount per program |
| Distribution by location | Barangay-level or city-level geographic breakdown |
| Distribution trend | Daily aggregation over time |
| Program progress | Budget and beneficiary progress per program |
| Top batches | Top distribution batches by beneficiary count |
| Recent activity | Recent distributions and registrations |
| **Date range filters** | Today, this week, this month, this quarter, this year, custom range, all time |
| Program filter | Filter all stats by specific program |
| **Role-based dashboards** | System Admin (full stats + user counts), Program Manager (annual program focus), Distribution Officer (weekly officer stats), Registration Officer (dedicated registration dashboard) |
| Registration Officer dashboard | Recent registrations, household/resident stats by barangay, demographic data |

### Mobile Resident Portal

| Feature | Description |
|---|---|
| Home screen | Personalized greeting, profile photo, QR code, contact info, demographic tags |
| Household card | Address, member count, dwelling type, utilities |
| Emergency contact | Display with call button |
| Account summary | Resident ID, active status badge, session expiry countdown |
| Ayuda summary | Pending count, received count, total amount received |

---

## 11. Reports & Data Export

### Web Admin Only

| Feature | Description |
|---|---|
| Distribution reports | By program, by date range, by status, by location |
| Resident reports | Demographic listings, ID card details |
| Geographic reports | By barangay, city/municipality, province |
| Program reports | Budget utilization, beneficiary progress |
| **CSV export** | UTF-8 with BOM for Excel compatibility, timestamp filenames |
| Streaming export | Memory-efficient export for large datasets |
| Resident CSV import | Template download, bulk import with error tracking |
| Batch image download | Photos, signatures, QR codes as ZIP |

---

## 12. Announcements

### Web Admin (Full CRUD)

| Feature | Description |
|---|---|
| Create announcements | Title, content, image upload |
| Types | General, Program, Distribution, Emergency, Maintenance |
| Priority levels | Low, Normal, High, Urgent |
| Publishing | Publish date/time, expiration date/time |
| Pin to top | Pin important announcements |
| Recipient targeting | All residents or program-specific beneficiaries |
| Active/inactive toggle | Control visibility |

### Mobile Resident Portal (View Only)

| Feature | Description |
|---|---|
| Announcement list | Paginated (10/page), expandable cards |
| Pinned indicators | Pinned announcements stay at top |
| Priority display | Visual priority indicators |
| Image support | View announcement images |
| Load more | Paginated loading |

---

## 13. Notifications & Push Notifications

### Web Admin (Management)

| Feature | Description |
|---|---|
| FCM integration | Firebase Cloud Messaging server-side |
| Device token management | Per-resident, multi-device, active/inactive status |
| Broadcast | Send notifications to all residents |
| Individual send | Send to specific residents |
| Failed token handling | Automatic deactivation of invalid tokens |

### Mobile Resident Portal (Receiving)

| Feature | Description |
|---|---|
| Push notifications | FCM integration with device token registration |
| In-app notifications | Notification list with unread indicators |
| Unread badge | Count of unread notifications |
| Mark as read | Individual and mark-all-as-read |
| Notification types | Announcements, ayuda updates, program updates, community alerts |
| Android notifications | Background notification display, notification channels (Android 8.0+) |
| Deep linking | Tap notification to navigate to relevant section |
| Token refresh | Automatic FCM token refresh handling |

---

## 14. User & Access Management

### Web Admin Only

| Feature | Description |
|---|---|
| User CRUD | Create, edit, deactivate admin users |
| Role assignment | Multiple roles per user |
| Direct permission assignment | Permissions directly on users |
| Role management | Create, edit, delete custom roles |
| Pre-defined roles | System Administrator, Program Manager, Distribution Officer, Registration Officer, Barangay Administrator, Staff |
| Permission categories | Residents, Households, Programs, Distributions, Reports, Users, Announcements, QR Codes, Verification, Settings |
| User search | Search and sort users |

---

## 15. System Settings & Configuration

### Web Admin Only

| Feature | Description |
|---|---|
| Key-value settings | Grouped settings with types (string, integer, boolean, JSON) |
| Public/private flags | Control which settings are exposed |
| Cache layer | Performance-optimized settings retrieval |
| Branding | Custom app logo and favicon |
| FCM configuration | Firebase server key management |
| Feature toggles | Enable/disable system features |

---

## 16. Activity Logging & Audit Trail

### Web Admin Only

| Feature | Description |
|---|---|
| Action logging | Module, action type, description |
| Change tracking | Old and new values (JSON diff) |
| User tracking | Who performed each action |
| Metadata | IP address, user agent, timestamp |
| Polymorphic | Linked to any model in the system |

---

## 17. Offline Functionality & Sync (Mobile)

### Mobile Resident Portal Only

| Feature | Description |
|---|---|
| **SQLite local database** | `ayudahub.db` for offline data storage |
| Profile caching | Resident profile stored locally |
| Session caching | Auth session persisted for offline login |
| Photo caching | Profile photos stored locally with file paths |
| Distribution caching | Last-known distribution state available offline |
| **Sync queue** | Tracks pending operations (profile updates, photo uploads, signature uploads) |
| Priority-based processing | Queue items processed by priority |
| Retry logic | Retry count and error tracking per queue item |
| Auto-sync | Automatic sync on internet reconnection |
| Manual sync | Pull-to-refresh triggers sync |
| Partial sync | Continues despite individual failures |
| Sync cleanup | Processed items cleaned up after 7 days |
| **Connectivity monitoring** | Real-time network status detection (WiFi/Cellular) |
| Connectivity notifications | Snackbar alerts for online/offline changes |
| Graceful degradation | API calls disabled when offline, cached data served |

---

## 18. Resident Profile & Self-Service (Mobile)

### Mobile Resident Portal Only

| Feature | Description |
|---|---|
| **Home screen** | Greeting, photo, QR code, contact info, demographic badges, household card, emergency contact, session info |
| **Profile screen** | Photo upload, editable fields, signature pad, password change, logout |
| **Digital ID screen** | Full-screen flip-able ID card |
| **Ayuda screen** | Summary cards (pending/received/total), pending section, all distributions (paginated), filters |
| **Updates screen** | Tabs for announcements and notifications, expandable cards, mark-as-read |
| **Bottom navigation** | Home, Ayuda, Digital ID (center/highlighted), Updates, Profile |
| Pull-to-refresh | Available on all screens |
| Session warning | Alert when session expires within 7 days |

---

## 19. Address / Location System

### Both Platforms

| Feature | Description |
|---|---|
| Geographic hierarchy | Region → Province → City/Municipality → Barangay |
| PSGC codes | Philippine Standard Geographic Code support |
| Cascading lookups | Select region → load provinces → load cities → load barangays |
| API endpoints | Full REST API for address data |

---

## 20. API Endpoints Summary

### Admin API (Web — 80+ endpoints)

| Module | Endpoints |
|---|---|
| Dashboard | 8 endpoints — stats, trends, program/barangay breakdowns, recent activity |
| Residents | 14 endpoints — CRUD, photo, QR, signature, ID card (single + batch) |
| Households | 5 endpoints — CRUD, members, QR, stats |
| Programs | 7 endpoints — CRUD, criteria management, eligibility check |
| Distributions | 8 endpoints — CRUD, receipt, verify, filtered by resident/household/program/batch |
| Batches | 6 endpoints — CRUD, distributions, stats, today/active |
| Settings | 6 endpoints — CRUD, group, cache clear |
| Address | 8 endpoints — regions, provinces, cities, barangays, individual lookups |

### Resident Portal API (Mobile — 20+ endpoints)

| Module | Endpoints |
|---|---|
| Auth | 5 endpoints — login, register, logout, change-password, refresh-token |
| Profile | 6 endpoints — get, update, photo, signature, QR code, ID card |
| Household | 2 endpoints — details, members |
| Programs | 3 endpoints — list, details, eligibility |
| Distributions | 5 endpoints — ayuda (undistributed first), list, summary, upcoming, details |
| Announcements | 2 endpoints — list, details |
| Notifications | 6 endpoints — list, unread-count, mark read, mark all read, register device, unregister device |

---

## Summary

| Metric | AyudaHub (Web Admin) | AyudaHubNative (Mobile) |
|---|---|---|
| **Primary Users** | Barangay staff & administrators | Barangay residents |
| **Core Purpose** | Manage residents, programs, distributions | View personal data, track aid, Digital ID |
| **Database Models** | 16 Eloquent models | 5 local SQLite models |
| **Livewire / Pages** | 27+ Livewire components | 5 main screens + dialogs |
| **API Endpoints** | 80+ admin + 20+ resident portal | Consumes resident portal API |
| **Roles** | 6 pre-defined roles | Single resident role |
| **Permissions** | 19+ granular permissions | N/A |
| **Offline Support** | N/A (web-based) | Full offline with sync queue |
| **Push Notifications** | Sends via FCM | Receives via FCM |
| **Key Differentiator** | Eligibility engine, batch distributions, reports | Digital ID, offline-first, QR display |
