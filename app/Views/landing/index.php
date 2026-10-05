<div class="landing-page" style="background: var(--surface-bg);">
    <!-- Hero Section -->
    <section class="landing-hero" style="padding: var(--space-16) var(--space-6) var(--space-12); text-align: center; border-bottom: 1px solid var(--border-subtle); background: radial-gradient(circle at 50% 10%, rgba(87, 157, 255, 0.08) 0%, transparent 60%);">
        <div style="max-width: 1040px; margin: 0 auto; position: relative;">
            <div class="inline-flex items-center gap-2 mb-6 px-3 py-1.5 rounded-full" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); font-size: 0.8125rem;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary); display: inline-block;"></span>
                <span class="text-secondary font-medium">Enterprise-grade CRM for Freelancers & Contractors</span>
            </div>

            <h1 class="font-heading" style="font-size: clamp(2.25rem, 5vw, 3.75rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.03em; margin-bottom: var(--space-5); color: var(--text-primary);">
                Stop project stalls.<br>
                <span style="color: var(--color-primary);">Always know whose turn it is.</span>
            </h1>

            <p class="text-muted mx-auto mb-8" style="max-width: 680px; font-size: 1.125rem; line-height: 1.6; color: var(--text-secondary);">
                WorkShift gives independent developers, designers, and consultants dedicated client boards that visually separate your active work from what's waiting on client feedback, content, or payment.
            </p>

            <div class="flex items-center justify-center gap-3 flex-wrap mb-8">
                <a href="/register" class="btn btn-primary btn-lg" style="height: 44px; padding: 0 24px; font-size: 0.9375rem; font-weight: 600;">
                    <span>Start Free Today</span>
                    <?= clay_icon('arrow-right', 16) ?>
                </a>
                <a href="/login" class="btn btn-secondary btn-lg" style="height: 44px; padding: 0 20px; font-size: 0.9375rem; font-weight: 600;">
                    <span>Sign In</span>
                </a>
            </div>

            <div class="flex items-center justify-center gap-6 text-xs text-muted font-semibold flex-wrap mb-12">
                <span class="flex items-center gap-1.5"><span class="text-success font-bold">✓</span> No credit card required</span>
                <span class="flex items-center gap-1.5"><span class="text-success font-bold">✓</span> 3 active clients free forever</span>
                <span class="flex items-center gap-1.5"><span class="text-success font-bold">✓</span> Setup in 60 seconds</span>
            </div>

            <!-- Interactive HTML/CSS Hero Mockup: The Kanban Board -->
            <div class="panel text-left p-6" style="background: var(--surface-card); border: 1px solid var(--border-outline); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal);">
                <!-- Mockup Header -->
                <div class="flex items-center justify-between flex-wrap gap-4 pb-4 mb-4" style="border-bottom: 1px solid var(--border-subtle);">
                    <div class="flex items-center gap-3">
                        <?= logo_svg(26) ?>
                        <div>
                            <div class="font-heading font-bold text-sm text-primary">Acme SaaS Redesign · Active Board</div>
                            <div class="text-xs text-muted">Client: Apex Studio Global · Next milestone Oct 15</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="lozenge lozenge-warning font-semibold">2 Blocked on Client</span>
                        <span class="lozenge lozenge-primary font-semibold">1 In Your Queue</span>
                    </div>
                </div>

                <!-- 4 Columns Tray -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-3);">
                    <!-- Col 1: Backlog -->
                    <div class="panel p-3" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                        <div class="flex items-center justify-between text-xs font-bold text-muted mb-3 uppercase tracking-wider">
                            <span>To Do</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                            <div class="text-xs font-mono text-muted mb-1">WS-101</div>
                            <div class="text-xs font-bold text-primary mb-1">Stripe Checkout Integration</div>
                            <div class="text-xs text-muted">Prepare webhook handlers and receipt UI</div>
                        </div>
                    </div>

                    <!-- Col 2: In Progress (Content Blocker) -->
                    <div class="panel p-3" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                        <div class="flex items-center justify-between text-xs font-bold text-muted mb-3 uppercase tracking-wider">
                            <span>In Progress</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                            <div class="text-xs font-mono text-muted mb-1">WS-102</div>
                            <div class="text-xs font-bold text-primary mb-2">Marketing Copy & Assets</div>
                            <div class="mb-2">
                                <span class="lozenge blocker-content font-bold" style="font-size: 0.6875rem;">
                                    <?= clay_icon('content', 12) ?>
                                    <span>Waiting on Content · 3d</span>
                                </span>
                            </div>
                            <div class="text-xs text-muted">Awaiting product screenshots from marketing</div>
                        </div>
                    </div>

                    <!-- Col 3: Review (Feedback Blocker) -->
                    <div class="panel p-3" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                        <div class="flex items-center justify-between text-xs font-bold text-muted mb-3 uppercase tracking-wider">
                            <span>Client Review</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                            <div class="text-xs font-mono text-muted mb-1">WS-103</div>
                            <div class="text-xs font-bold text-primary mb-2">Design System Tokens</div>
                            <div class="mb-2">
                                <span class="lozenge blocker-feedback font-bold" style="font-size: 0.6875rem;">
                                    <?= clay_icon('feedback', 12) ?>
                                    <span>Waiting on Feedback · 2d</span>
                                </span>
                            </div>
                            <div class="text-xs text-muted">Figma handoff ready for client sign-off</div>
                        </div>
                    </div>

                    <!-- Col 4: Done -->
                    <div class="panel p-3" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                        <div class="flex items-center justify-between text-xs font-bold text-muted mb-3 uppercase tracking-wider">
                            <span>Done</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                            <div class="text-xs font-mono text-muted mb-1">WS-98</div>
                            <div class="text-xs font-bold text-primary mb-1">Database Architecture Setup</div>
                            <span class="lozenge lozenge-success font-bold" style="font-size: 0.6875rem;">✓ Signed off</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3 Core Feature Sections with HTML/CSS Mockups (64-96px spacing) -->
    <!-- Feature 1: The Blocker Engine -->
    <section style="padding: var(--space-16) var(--space-6); border-bottom: 1px solid var(--border-subtle); background: var(--surface-card);">
        <div style="max-width: 1120px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-12); align-items: center;">
            <div>
                <div class="lozenge lozenge-primary font-bold mb-3 uppercase tracking-wider">1. The Blocker Engine</div>
                <h2 class="font-heading text-2xl md:text-3xl font-bold mb-4 text-primary" style="letter-spacing: -0.02em;">
                    Pinpoint exact bottlenecks.<br>End "Where is my deliverable?"
                </h2>
                <p class="text-secondary text-sm leading-relaxed mb-4">
                    Generic project tools let client tasks sit quietly in limbo. WorkShift tags stalled deliverables with structured blocker reasons: <strong>Feedback</strong>, <strong>Content</strong>, <strong>Payment</strong>, or <strong>Scheduling</strong>.
                </p>
                <ul class="flex flex-col gap-2 text-xs font-medium text-secondary mb-6" style="list-style: none; padding: 0;">
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Tracks exact days elapsed waiting on client</li>
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Automated gentle nudge emails after 3 days</li>
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Clear accountability: shows whether the ball is in your court or theirs</li>
                </ul>
            </div>

            <!-- Mockup: Blocker Card -->
            <div class="panel p-5" style="background: var(--surface-tray); border: 1px solid var(--border-outline); border-radius: var(--radius-md);">
                <div class="panel p-4" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-mono text-xs text-muted">WS-104</span>
                        <span class="lozenge blocker-content font-bold">Waiting on Content · 4 days</span>
                    </div>
                    <h4 class="font-bold text-sm text-primary mb-1">E-Commerce Catalog Photography</h4>
                    <p class="text-xs text-secondary mb-3">Client must upload 24 high-res product packshots to Dropbox before staging build can proceed.</p>
                    <div class="p-2.5 rounded text-xs flex items-center justify-between" style="background: var(--surface-well); border: 1px solid var(--border-subtle);">
                        <span class="text-muted">Waiting on: <strong class="text-primary">Acme Marketing Team</strong></span>
                        <span class="text-xs font-semibold text-primary">Automated reminder sent today</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 2: Shared Time Tracker -->
    <section style="padding: var(--space-16) var(--space-6); border-bottom: 1px solid var(--border-subtle); background: var(--surface-bg);">
        <div style="max-width: 1120px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-12); align-items: center;">
            <div class="order-2 md:order-1">
                <!-- Mockup: Time Tracker -->
                <div class="panel p-5" style="background: var(--surface-tray); border: 1px solid var(--border-outline); border-radius: var(--radius-md);">
                    <div class="panel p-4" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                        <div class="flex items-center justify-between pb-3 mb-3" style="border-bottom: 1px solid var(--border-subtle);">
                            <div class="flex items-center gap-2">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: #22C55E; display: inline-block;"></span>
                                <span class="font-mono text-xs font-bold text-primary">02:45:18</span>
                            </div>
                            <span class="lozenge lozenge-primary font-bold">Active Timer</span>
                        </div>
                        <div class="text-xs font-bold text-primary mb-1">Task: Frontend Architecture & Layouts</div>
                        <div class="text-xs text-muted mb-4">Client: Fintech Global · Rate: ₱1,800/hr</div>
                        <div class="flex items-center justify-between text-xs pt-2" style="border-top: 1px solid var(--border-subtle);">
                            <span class="text-muted">Synced to client portal</span>
                            <span class="font-bold text-success">₱4,950.00 accrued</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-1 md:order-2">
                <div class="lozenge lozenge-primary font-bold mb-3 uppercase tracking-wider">2. Shared Time Tracker</div>
                <h2 class="font-heading text-2xl md:text-3xl font-bold mb-4 text-primary" style="letter-spacing: -0.02em;">
                    Transparent hours.<br>Zero invoice disputes.
                </h2>
                <p class="text-secondary text-sm leading-relaxed mb-4">
                    Clients never question an invoice when they can inspect live time logs directly inside their portal. WorkShift gives you one-tap live timers, manual entry, and a privacy toggle when logs are internal.
                </p>
                <ul class="flex flex-col gap-2 text-xs font-medium text-secondary mb-6" style="list-style: none; padding: 0;">
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> One-click conversion from tracked hours to invoices</li>
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Client portal transparency builds trust</li>
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Private entries stay invisible to clients</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Feature 3: Passwordless Client Portal -->
    <section style="padding: var(--space-16) var(--space-6); border-bottom: 1px solid var(--border-subtle); background: var(--surface-card);">
        <div style="max-width: 1120px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-12); align-items: center;">
            <div>
                <div class="lozenge lozenge-primary font-bold mb-3 uppercase tracking-wider">3. Passwordless Client Portal</div>
                <h2 class="font-heading text-2xl md:text-3xl font-bold mb-4 text-primary" style="letter-spacing: -0.02em;">
                    Frictionless sign-offs.<br>No logins for clients to forget.
                </h2>
                <p class="text-secondary text-sm leading-relaxed mb-4">
                    Clients hate creating yet another account. WorkShift gives each client an unguessable private portal link where they can inspect deliverables, upload assets, approve milestones, and settle invoices via Maya QR Ph.
                </p>
                <ul class="flex flex-col gap-2 text-xs font-medium text-secondary mb-6" style="list-style: none; padding: 0;">
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> One-click deliverable approval & revision notes</li>
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Built-in file sharing & asset uploads</li>
                    <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Seamless Maya QR Ph & Credit Card payments</li>
                </ul>
            </div>

            <!-- Mockup: Client Portal Sign-Off -->
            <div class="panel p-5" style="background: var(--surface-tray); border: 1px solid var(--border-outline); border-radius: var(--radius-md);">
                <div class="panel p-4" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-bold text-xs text-primary">Deliverable Sign-Off</span>
                        <span class="lozenge lozenge-warning font-semibold">Review Pending</span>
                    </div>
                    <div class="text-xs font-bold text-primary mb-1">Milestone 2: Final Brand Identity Package</div>
                    <div class="text-xs text-muted mb-3">Contains logos, color specs, and font licenses (ZIP, 42MB)</div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn btn-success btn-sm font-semibold" style="flex: 1;">✓ Approve & Sign Off</button>
                        <button type="button" class="btn btn-secondary btn-sm" style="flex: 1;">Request Revisions</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section (Basic vs Pro ₱499/mo) -->
    <section id="pricing" style="padding: var(--space-16) var(--space-6); border-bottom: 1px solid var(--border-subtle); background: var(--surface-bg);">
        <div style="max-width: 960px; margin: 0 auto;">
            <div class="text-center mb-12">
                <span class="lozenge lozenge-primary font-bold mb-2 uppercase tracking-wider">Simple Pricing</span>
                <h2 class="font-heading text-3xl font-bold mb-3 text-primary" style="letter-spacing: -0.02em;">Honest, Freelancer-First Pricing</h2>
                <p class="text-muted max-w-lg mx-auto text-sm">Tailored specifically for independent contractors. No hidden tiers, no confusing per-seat charges.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-6); max-width: 820px; margin: 0 auto;">
                <!-- Basic Plan -->
                <div class="panel p-8 flex flex-col justify-between" style="background: var(--surface-card); border: 1px solid var(--border-outline); border-radius: var(--radius-md);">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-heading text-xl font-bold text-primary m-0">Basic</h3>
                            <span class="lozenge lozenge-muted font-semibold">Free Forever</span>
                        </div>
                        <p class="text-xs text-muted mb-4">For independent freelancers managing a select client roster.</p>
                        <div class="font-heading text-3xl font-bold mb-6 text-primary">
                            <span>₱0</span>
                            <span class="text-xs text-muted font-normal">/ month</span>
                        </div>

                        <ul class="flex flex-col gap-2.5 text-xs text-secondary mb-8" style="list-style: none; padding: 0;">
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> <strong>Up to 3 active clients</strong></li>
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Dedicated client boards & Kanban</li>
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Signature blocker engine</li>
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Built-in time tracker & logging</li>
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Client portal (view & approve)</li>
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Standard invoices & print layouts</li>
                            <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> 2 GB secure storage</li>
                        </ul>
                    </div>

                    <a href="/register" class="btn btn-secondary w-full text-center font-semibold">
                        Get Started Free
                    </a>
                </div>

                <!-- Pro Plan -->
                <div class="panel p-8 flex flex-col justify-between" style="background: var(--surface-card); border: 2px solid var(--color-primary); border-radius: var(--radius-md); position: relative;">
                    <div style="position: absolute; top: -11px; right: 24px;">
                        <span class="lozenge lozenge-primary font-bold uppercase tracking-wider" style="font-size: 0.6875rem; padding: 2px 10px;">Recommended</span>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-heading text-xl font-bold text-primary m-0">WorkShift Pro</h3>
                        </div>
                        <p class="text-xs text-muted mb-4">For active freelancers scaling projects and automated billing.</p>
                        <div class="font-heading text-3xl font-bold mb-6 text-primary">
                            <span>₱499</span>
                            <span class="text-xs text-muted font-normal">/ month</span>
                        </div>

                        <ul class="flex flex-col gap-2.5 text-xs text-secondary mb-8" style="list-style: none; padding: 0;">
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> <strong>Unlimited active clients</strong></li>
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> <strong>In-app client payments (Maya QR Ph & cards)</strong></li>
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> <strong>Automated blocker reminder emails</strong></li>
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> Custom scheduling & booking links</li>
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> Flexible billing (hourly & fixed rates)</li>
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> Private time logs toggle</li>
                            <li class="flex items-center gap-2"><span class="text-primary font-bold">✓</span> <strong>50 GB secure storage</strong></li>
                        </ul>
                    </div>

                    <a href="/register" class="btn btn-primary w-full text-center font-semibold">
                        Upgrade to Pro (₱499/mo)
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Accordion Section -->
    <section style="padding: var(--space-16) var(--space-6); border-bottom: 1px solid var(--border-subtle); background: var(--surface-card);">
        <div style="max-width: 760px; margin: 0 auto;">
            <div class="text-center mb-10">
                <span class="lozenge lozenge-muted font-bold mb-2 uppercase tracking-wider">Got Questions?</span>
                <h2 class="font-heading text-2xl font-bold text-primary">Frequently Asked Questions</h2>
            </div>

            <div class="flex flex-col gap-4">
                <div class="panel p-4" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                    <h4 class="font-bold text-sm text-primary mb-1">Do my clients have to create an account?</h4>
                    <p class="text-xs text-secondary m-0 leading-relaxed">No. Every client gets a unique, private unguessable portal URL. They can view tasks, approve milestones, upload files, and pay invoices without remembering a password.</p>
                </div>

                <div class="panel p-4" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                    <h4 class="font-bold text-sm text-primary mb-1">How does the 3-client free tier work?</h4>
                    <p class="text-xs text-secondary m-0 leading-relaxed">You can have up to 3 active clients simultaneously at zero cost forever. If you complete a project, you can archive that client to free up a slot, or upgrade to Pro for ₱499/mo to unlock unlimited clients.</p>
                </div>

                <div class="panel p-4" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                    <h4 class="font-bold text-sm text-primary mb-1">How do client payments work in the Philippines?</h4>
                    <p class="text-xs text-secondary m-0 leading-relaxed">WorkShift integrates with Maya Checkout. Your clients can settle invoices using QR Ph (compatible with GCash, Maya, BDO, BPI, etc.) or standard debit/credit cards.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom CTA Bar -->
    <section style="padding: var(--space-16) var(--space-6); text-align: center; background: var(--surface-tray);">
        <div style="max-width: 640px; margin: 0 auto;">
            <h2 class="font-heading text-2xl md:text-3xl font-bold mb-3 text-primary" style="letter-spacing: -0.02em;">Ready to bring order to your client work?</h2>
            <p class="text-secondary text-sm mb-6">Join independent freelancers who never have to ask "whose turn is it?" again.</p>
            <a href="/register" class="btn btn-primary btn-lg font-semibold" style="height: 44px; padding: 0 24px;">
                <span>Create Your Free Account Now</span>
                <?= clay_icon('arrow-right', 16) ?>
            </a>
        </div>
    </section>
</div>
