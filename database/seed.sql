-- WorkShift Seed Data
-- Demo Freelancer and realistic clients, boards, tasks, blockers, time entries, invoices

SET FOREIGN_KEY_CHECKS = 0;

-- Demo Freelancer (Password: password123)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `company_name`, `plan`, `scheduling_link`, `hourly_rate`, `currency`, `storage_used_bytes`, `created_at`)
VALUES (
  1,
  'Alex Rivera',
  'alex@studioclickup.com',
  '$2y$10$XQWvlpG5sxouq8uWxNpCOugb4NoR0xL77/cndSCZArX/FBHi0g6mC',
  'Studio Click Up',
  'pro',
  'https://cal.com/alex-rivera-freelance',
  750.00,
  'PHP',
  1450000,
  DATE_SUB(NOW(), INTERVAL 30 DAY)
);

-- Demo Clients
INSERT INTO `clients` (`id`, `user_id`, `name`, `company`, `email`, `phone`, `notes`, `billing_type`, `rate`, `pipeline_stage`, `created_at`)
VALUES
(
  1,
  1,
  'Maria Santos',
  'Acme Tech PH',
  'maria@acmetech.ph',
  '+63 917 123 4567',
  'Full web redesign and SaaS customer dashboard revamp. Communication via client portal and email.',
  'hourly',
  850.00,
  'active',
  DATE_SUB(NOW(), INTERVAL 25 DAY)
),
(
  2,
  1,
  'Carlos Mendoza',
  'Boutique Brew Cafe',
  'carlos@boutiquebrew.ph',
  '+63 918 555 7890',
  'E-commerce coffee subscription shop and local store locator. Fixed milestone contract.',
  'fixed',
  45000.00,
  'active',
  DATE_SUB(NOW(), INTERVAL 18 DAY)
),
(
  3,
  1,
  'Elena Gomez',
  'Island Peak Resorts',
  'elena@islandpeak.ph',
  '+63 920 888 1122',
  'High-end Palawan resort booking landing page and visual brochure.',
  'hourly',
  950.00,
  'active',
  DATE_SUB(NOW(), INTERVAL 10 DAY)
);

-- Portal Tokens for secure client links
INSERT INTO `portal_tokens` (`id`, `client_id`, `token`, `is_active`, `last_accessed_at`, `created_at`)
VALUES
(1, 1, 'acme-portal-demo-token-12345', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW()),
(2, 2, 'brew-portal-demo-token-67890', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(3, 3, 'island-portal-demo-token-54321', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW());

-- Dedicated Boards (One per client)
INSERT INTO `boards` (`id`, `client_id`, `user_id`, `title`, `created_at`)
VALUES
(1, 1, 1, 'Acme Tech SaaS Redesign', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(2, 2, 1, 'Boutique Brew Shop Launch', DATE_SUB(NOW(), INTERVAL 18 DAY)),
(3, 3, 1, 'Island Peak Booking Page', DATE_SUB(NOW(), INTERVAL 10 DAY));

-- Stages for Board 1 (Acme Tech)
INSERT INTO `stages` (`id`, `board_id`, `title`, `position`, `is_review_stage`, `is_done_stage`)
VALUES
(1, 1, 'To Do', 0, 0, 0),
(2, 1, 'In Progress', 1, 0, 0),
(3, 1, 'In Review', 2, 1, 0),
(4, 1, 'Done', 3, 0, 1);

-- Stages for Board 2 (Boutique Brew)
INSERT INTO `stages` (`id`, `board_id`, `title`, `position`, `is_review_stage`, `is_done_stage`)
VALUES
(5, 2, 'To Do', 0, 0, 0),
(6, 2, 'In Progress', 1, 0, 0),
(7, 2, 'In Review', 2, 1, 0),
(8, 2, 'Done', 3, 0, 1);

-- Stages for Board 3 (Island Peak)
INSERT INTO `stages` (`id`, `board_id`, `title`, `position`, `is_review_stage`, `is_done_stage`)
VALUES
(9, 3, 'To Do', 0, 0, 0),
(10, 3, 'In Progress', 1, 0, 0),
(11, 3, 'In Review', 2, 1, 0),
(12, 3, 'Done', 3, 0, 1);

-- Tasks for Board 1 (Acme Tech)
INSERT INTO `tasks` (`id`, `board_id`, `stage_id`, `user_id`, `title`, `description`, `due_date`, `position`, `review_status`)
VALUES
(1, 1, 2, 1, 'Dashboard Metrics Analytics View', 'Build reactive chart components showing monthly active subscribers and MRR breakdown.', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 0, 'pending'),
(2, 1, 3, 1, 'Billing Settings & Invoice Table', 'Completed full responsive view for managing payment methods and past receipts. Awaiting client signoff.', CURRENT_DATE, 0, 'pending'),
(3, 1, 1, 1, 'Customer Onboarding Flow Mockups', 'Create wireframes for 3-step organization setup wizard.', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 0, 'pending'),
(4, 1, 4, 1, 'Design System Foundation & Colors', 'Tokens, button primitives, typography scales, and iconography set in Figma.', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 0, 'approved');

-- Tasks for Board 2 (Boutique Brew)
INSERT INTO `tasks` (`id`, `board_id`, `stage_id`, `user_id`, `title`, `description`, `due_date`, `position`, `review_status`)
VALUES
(5, 2, 2, 1, 'Single-Origin Coffee Product Page', 'Need high-res bag packaging photos and bean origin tasting notes from client to finalize layout.', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 0, 'pending'),
(6, 2, 1, 1, 'Checkout Step & Maya QR Ph Setup', 'Integrate direct Maya checkout flow for mobile banking customers.', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 0, 'pending'),
(7, 2, 3, 1, 'Subscription Delivery Calculator', 'Custom delivery interval selector (weekly / bi-weekly / monthly). Tested on mobile browsers.', CURRENT_DATE, 0, 'pending'),
(8, 2, 4, 1, 'Initial Scope & Brand Moodboard', 'Approved direction: earthy tones, clean craft minimalism.', DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), 0, 'approved');

-- Tasks for Board 3 (Island Peak)
INSERT INTO `tasks` (`id`, `board_id`, `stage_id`, `user_id`, `title`, `description`, `due_date`, `position`, `review_status`)
VALUES
(9, 3, 1, 1, 'Onboarding Discovery & Intake Call', 'Kickoff call to align on visual assets, drone footage delivery, and booking calendar integration.', CURRENT_DATE, 0, 'pending'),
(10, 3, 1, 1, 'Villa Hero Gallery & Amenities Grid', 'High-impact grid showcasing oceanfront villas, private pools, and dining experiences.', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY), 1, 'pending');

-- Task Blockers (Signature feature: ONE blocker per task, days waiting calculated live)
-- Task 1: Blocker on Client -> Feedback (waiting 3 days)
-- Task 5: Blocker on Client -> Content (waiting 4 days)
-- Task 9: Blocker on Client -> Scheduling (waiting 2 days)
-- Task 6: Blocker on Client -> Payment (waiting 5 days)
INSERT INTO `task_blockers` (`id`, `task_id`, `type`, `reason`, `waiting_on`, `waiting_since`)
VALUES
(1, 2, 'feedback', 'Need Maria to review the invoice line-item layout and approve columns before moving to final Done.', 'client', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 5, 'content', 'Waiting for high-resolution bag photos (Robusta & Arabica batches) and copy from marketing.', 'client', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 9, 'scheduling', 'Waiting for Elena to select a 30-min discovery slot via our calendar link.', 'client', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 6, 'payment', 'Phase 2 milestone deposit (₱15,000) pending bank transfer confirmation.', 'client', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Time Entries
INSERT INTO `time_entries` (`id`, `task_id`, `user_id`, `client_id`, `entry_date`, `duration_minutes`, `notes`, `is_private`, `is_running`, `timer_started_at`)
VALUES
(1, 4, 1, 1, DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), 240, 'Created Figma typography scale, color tokens, and button states.', 0, 0, NULL),
(2, 4, 1, 1, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 180, 'Exported SVG assets and documented CSS variables.', 0, 0, NULL),
(3, 2, 1, 1, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 210, 'Built invoice table markup and responsive overflow states.', 0, 0, NULL),
(4, 1, 1, 1, CURRENT_DATE, 150, 'Drafted Chart.js metrics widgets and summary cards.', 0, 0, NULL),
(5, 8, 1, 2, DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY), 120, 'Internal competitive analysis on local coffee roasters.', 1, 0, NULL),
(6, 7, 1, 2, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 240, 'Built delivery cadence selector and discount calculation logic.', 0, 0, NULL);

-- Comments on tasks
INSERT INTO `comments` (`id`, `task_id`, `user_id`, `client_id`, `author_name`, `is_client`, `content`, `created_at`)
VALUES
(1, 2, 1, NULL, 'Alex Rivera', 0, 'Hi Maria, I have deployed the staging preview for the Billing table. Please click Approve above when ready!', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 2, NULL, 1, 'Maria Santos', 1, 'Looks very sleek Alex! I will check the tax calculations with our accountant this afternoon.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 5, 1, NULL, 'Alex Rivera', 0, 'Hey Carlos! Just a reminder to send over the raw high-res package photos so I can clip them into the hero cards.', DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Invoices
INSERT INTO `invoices` (`id`, `user_id`, `client_id`, `invoice_number`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `total_amount`, `currency`, `status`, `notes`, `paid_at`, `created_at`)
VALUES
(
  1,
  1,
  1,
  'INV-2026-001',
  DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY),
  DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY),
  13175.00,
  0.00,
  0.00,
  13175.00,
  'PHP',
  'overdue',
  'Sprint 1 UI Deliverables: Design system setup (7 hrs) + Billing table implementation (8.5 hrs) @ ₱850/hr.',
  NULL,
  DATE_SUB(NOW(), INTERVAL 10 DAY)
),
(
  2,
  1,
  2,
  'INV-2026-002',
  DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY),
  DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY),
  22500.00,
  0.00,
  0.00,
  22500.00,
  'PHP',
  'sent',
  'Phase 1 Milestone: Brand discovery, information architecture, and subscription wireframes (50% upfront).',
  NULL,
  DATE_SUB(NOW(), INTERVAL 5 DAY)
);

-- Invoice Items
INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total`)
VALUES
(1, 1, 'UI Design System Foundation & Component Library', 7.00, 850.00, 5950.00),
(2, 1, 'Billing & Invoicing Responsive Layout Implementation', 8.50, 850.00, 7225.00),
(3, 2, 'E-Commerce Setup Phase 1 Milestone (50% Deposit)', 1.00, 22500.00, 22500.00);

-- Notifications
INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link_url`, `is_read`, `created_at`)
VALUES
(1, 1, 'client_approved', 'Task Approved by Client', 'Maria Santos (Acme Tech PH) approved "Design System Foundation & Colors".', '/boards/1', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, 'client_feedback', 'New Comment from Client', 'Maria Santos commented on "Billing Settings & Invoice Table".', '/boards/1', 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 1, 'blocker_stalled', 'Blocker Stalled (4 Days)', '"Single-Origin Coffee Product Page" has been waiting on client content for 4 days.', '/boards/2', 0, DATE_SUB(NOW(), INTERVAL 12 HOUR));

SET FOREIGN_KEY_CHECKS = 1;
