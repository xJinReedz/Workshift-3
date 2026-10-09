<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 25px rgba(76, 154, 255, 0.15); }
    50% { box-shadow: 0 0 45px rgba(76, 154, 255, 0.35); }
}

@keyframes slide-up-fade {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-glow {
    animation: pulse-glow 4s ease-in-out infinite;
}

.animate-appear {
    animation: slide-up-fade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.glass-card {
    background: rgba(22, 28, 36, 0.75) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.glass-card:hover {
    transform: translateY(-4px);
    border-color: rgba(76, 154, 255, 0.4) !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
}
</style>

<div class="landing-page" style="background: var(--color-bg-page, #0A0D12); color: var(--color-text, #F4F5F7);">
    <!-- Hero Section -->
    <section class="landing-hero" style="padding: var(--space-16) var(--space-6) var(--space-12); text-align: center; border-bottom: 1px solid var(--color-border); background: radial-gradient(circle at 50% 10%, rgba(76, 154, 255, 0.12) 0%, transparent 65%);">
        <div class="animate-appear" style="max-width: 1040px; margin: 0 auto; position: relative;">
            <div class="inline-flex items-center gap-2 mb-6 px-3.5 py-1.5 rounded-full glass-card" style="font-size: 0.8125rem;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #4C9AFF; display: inline-block; box-shadow: 0 0 10px #4C9AFF;"></span>
                <span class="text-secondary font-medium">Enterprise-grade CRM for Freelancers & Independent Agencies</span>
            </div>

            <h1 class="font-heading" style="font-size: clamp(2.5rem, 5.5vw, 4rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.03em; margin-bottom: var(--space-5); color: #FFFFFF;">
                Stop project stalls.<br>
                <span style="background: linear-gradient(135deg, #4C9AFF 0%, #00B8D9 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Always know whose turn it is.</span>
            </h1>

            <p class="text-muted mx-auto mb-8" style="max-width: 680px; font-size: 1.125rem; line-height: 1.6; color: #A5ADBA;">
                WorkShift gives freelancers dedicated client boards that visually separate active deliverables from what's waiting on client feedback, content, or payment.
            </p>

            <div class="flex items-center justify-center gap-3 flex-wrap mb-8">
                <a href="/register" class="btn btn-primary btn-lg font-semibold animate-glow" style="height: 48px; padding: 0 28px; font-size: 0.9375rem; border-radius: 8px;">
                    <span>Start Free Today</span>
                    <?= clay_icon('arrow-right', 16) ?>
                </a>
                <a href="/login" class="btn btn-secondary btn-lg font-semibold glass-card" style="height: 48px; padding: 0 24px; font-size: 0.9375rem; border-radius: 8px;">
                    <span>Sign In</span>
                </a>
            </div>

            <div class="flex items-center justify-center gap-6 text-xs text-muted font-semibold flex-wrap mb-12" style="color: #6B778C;">
                <span class="flex items-center gap-1.5"><span style="color: #36B37E; font-weight: bold;">✓</span> No credit card required</span>
                <span class="flex items-center gap-1.5"><span style="color: #36B37E; font-weight: bold;">✓</span> 3 active clients free forever</span>
                <span class="flex items-center gap-1.5"><span style="color: #36B37E; font-weight: bold;">✓</span> Setup in 60 seconds</span>
            </div>

            <!-- Interactive Kanban Board Hero Mockup -->
            <div class="panel text-left p-6 glass-card animate-float" style="border-radius: 12px;">
                <div class="flex items-center justify-between flex-wrap gap-4 pb-4 mb-4" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <div class="flex items-center gap-3">
                        <?= logo_svg(28) ?>
                        <div>
                            <div class="font-heading font-bold text-sm text-primary" style="color: #FFF;">Acme SaaS Redesign · Client Workspace</div>
                            <div class="text-xs text-muted" style="color: #A5ADBA;">Client: Apex Studio Global · Next Milestone Oct 15</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="lozenge lozenge-warning font-semibold" style="background: rgba(255, 171, 0, 0.15); color: #FFAB00;">2 Waiting on Client</span>
                        <span class="lozenge lozenge-primary font-semibold" style="background: rgba(76, 154, 255, 0.15); color: #4C9AFF;">1 In Your Queue</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-3);">
                    <!-- Col 1: To Do -->
                    <div class="panel p-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px;">
                        <div class="flex items-center justify-between text-xs font-bold mb-3 uppercase tracking-wider" style="color: #8993A4;">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full inline-block" style="background: #4C9AFF;"></span>To Do</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2 glass-card" style="border-radius: 6px;">
                            <div class="text-xs font-mono text-muted mb-1" style="color: #6B778C;">WS-101</div>
                            <div class="text-xs font-bold text-primary mb-1" style="color: #FFF;">Stripe & Maya Payment Gateway</div>
                            <div class="text-xs text-muted" style="color: #8993A4;">Prepare webhook handlers and receipt modal UI</div>
                        </div>
                    </div>

                    <!-- Col 2: In Progress -->
                    <div class="panel p-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px;">
                        <div class="flex items-center justify-between text-xs font-bold mb-3 uppercase tracking-wider" style="color: #8993A4;">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full inline-block" style="background: #F5CD47;"></span>In Progress</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2 glass-card" style="border-radius: 6px;">
                            <div class="text-xs font-mono text-muted mb-1" style="color: #6B778C;">WS-102</div>
                            <div class="text-xs font-bold text-primary mb-2" style="color: #FFF;">Marketing Copy & Photography</div>
                            <div class="mb-2">
                                <span class="lozenge blocker-content font-bold" style="font-size: 0.6875rem; background: rgba(255, 171, 0, 0.15); color: #FFAB00;">
                                    <?= clay_icon('content', 12) ?>
                                    <span>Waiting on Content · 3d</span>
                                </span>
                            </div>
                            <div class="text-xs text-muted" style="color: #8993A4;">Awaiting product photos from marketing team</div>
                        </div>
                    </div>

                    <!-- Col 3: Client Review -->
                    <div class="panel p-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px;">
                        <div class="flex items-center justify-between text-xs font-bold mb-3 uppercase tracking-wider" style="color: #8993A4;">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full inline-block" style="background: #9F8FEF;"></span>In Review</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2 glass-card" style="border-radius: 6px;">
                            <div class="text-xs font-mono text-muted mb-1" style="color: #6B778C;">WS-103</div>
                            <div class="text-xs font-bold text-primary mb-2" style="color: #FFF;">Design System Tokens</div>
                            <div class="mb-2">
                                <span class="lozenge blocker-feedback font-bold" style="font-size: 0.6875rem; background: rgba(159, 143, 239, 0.15); color: #9F8FEF;">
                                    <?= clay_icon('feedback', 12) ?>
                                    <span>Client Review · 2d</span>
                                </span>
                            </div>
                            <div class="text-xs text-muted" style="color: #8993A4;">Figma handoff ready for client sign-off</div>
                        </div>
                    </div>

                    <!-- Col 4: Done -->
                    <div class="panel p-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px;">
                        <div class="flex items-center justify-between text-xs font-bold mb-3 uppercase tracking-wider" style="color: #8993A4;">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full inline-block" style="background: #57D9A3;"></span>Done</span>
                            <span class="lozenge lozenge-muted">1</span>
                        </div>
                        <div class="panel p-3 mb-2 glass-card" style="border-radius: 6px;">
                            <div class="text-xs font-mono text-muted mb-1" style="color: #6B778C;">WS-98</div>
                            <div class="text-xs font-bold text-primary mb-1" style="color: #FFF;">Database Migration Architecture</div>
                            <span class="lozenge lozenge-success font-bold" style="font-size: 0.6875rem; background: rgba(54, 179, 126, 0.15); color: #36B37E;">✓ Signed off</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" style="padding: var(--space-16) var(--space-6); border-bottom: 1px solid var(--color-border); background: var(--color-bg-page);">
        <div style="max-width: 960px; margin: 0 auto;">
            <div class="text-center mb-12">
                <span class="lozenge lozenge-primary font-bold mb-2 uppercase tracking-wider" style="background: rgba(76,154,255,0.15); color: #4C9AFF;">Simple Pricing</span>
                <h2 class="font-heading text-3xl font-bold mb-3" style="color: #FFF; letter-spacing: -0.02em;">Honest Freelancer Pricing</h2>
                <p class="text-muted max-w-lg mx-auto text-sm" style="color: #8993A4;">No hidden tiers or complex seat formulas.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-6); max-width: 820px; margin: 0 auto;">
                <div class="panel p-8 flex flex-col justify-between glass-card">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-heading text-xl font-bold text-primary m-0" style="color: #FFF;">Basic</h3>
                            <span class="lozenge lozenge-muted font-semibold">Free Forever</span>
                        </div>
                        <p class="text-xs text-muted mb-4" style="color: #8993A4;">For independent freelancers managing a core roster.</p>
                        <div class="font-heading text-3xl font-bold mb-6 text-primary" style="color: #FFF;">
                            <span>₱0</span>
                            <span class="text-xs text-muted font-normal" style="color: #8993A4;">/ month</span>
                        </div>

                        <ul class="flex flex-col gap-2.5 text-xs mb-8" style="list-style: none; padding: 0; color: #A5ADBA;">
                            <li class="flex items-center gap-2"><span style="color: #36B37E; font-weight: bold;">✓</span> <strong>Up to 3 active clients</strong></li>
                            <li class="flex items-center gap-2"><span style="color: #36B37E; font-weight: bold;">✓</span> Dedicated client boards</li>
                            <li class="flex items-center gap-2"><span style="color: #36B37E; font-weight: bold;">✓</span> Independent stage engine & optional review toggle</li>
                            <li class="flex items-center gap-2"><span style="color: #36B37E; font-weight: bold;">✓</span> Built-in time tracker & logging</li>
                            <li class="flex items-center gap-2"><span style="color: #36B37E; font-weight: bold;">✓</span> Real client account authorization</li>
                        </ul>
                    </div>
                    <a href="/register" class="btn btn-secondary w-full text-center font-semibold glass-card">Get Started Free</a>
                </div>

                <div class="panel p-8 flex flex-col justify-between glass-card" style="border-color: #4C9AFF !important; box-shadow: 0 0 30px rgba(76, 154, 255, 0.2);">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-heading text-xl font-bold text-primary m-0" style="color: #FFF;">WorkShift Pro</h3>
                            <span class="lozenge lozenge-primary font-bold uppercase tracking-wider" style="background: rgba(76,154,255,0.2); color: #4C9AFF;">Pro Tier</span>
                        </div>
                        <p class="text-xs text-muted mb-4" style="color: #8993A4;">For active freelancers scaling projects and automated billing.</p>
                        <div class="font-heading text-3xl font-bold mb-6 text-primary" style="color: #FFF;">
                            <span>₱499</span>
                            <span class="text-xs text-muted font-normal" style="color: #8993A4;">/ month</span>
                        </div>

                        <ul class="flex flex-col gap-2.5 text-xs mb-8" style="list-style: none; padding: 0; color: #A5ADBA;">
                            <li class="flex items-center gap-2"><span style="color: #4C9AFF; font-weight: bold;">✓</span> <strong>Unlimited active clients</strong></li>
                            <li class="flex items-center gap-2"><span style="color: #4C9AFF; font-weight: bold;">✓</span> <strong>In-app client payments (Maya QR Ph)</strong></li>
                            <li class="flex items-center gap-2"><span style="color: #4C9AFF; font-weight: bold;">✓</span> <strong>Automated blocker reminder emails</strong></li>
                            <li class="flex items-center gap-2"><span style="color: #4C9AFF; font-weight: bold;">✓</span> Custom scheduling & booking links</li>
                        </ul>
                    </div>
                    <a href="/register" class="btn btn-primary w-full text-center font-semibold animate-glow">Upgrade to Pro (₱499/mo)</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom CTA Bar -->
    <section style="padding: var(--space-16) var(--space-6); text-align: center; background: radial-gradient(circle at 50% 50%, rgba(76,154,255,0.08) 0%, transparent 70%);">
        <div style="max-width: 640px; margin: 0 auto;">
            <h2 class="font-heading text-2xl md:text-3xl font-bold mb-3" style="color: #FFF; letter-spacing: -0.02em;">Ready to bring order to your client work?</h2>
            <p class="text-secondary text-sm mb-6" style="color: #8993A4;">Join independent freelancers who never have to ask "whose turn is it?" again.</p>
            <a href="/register" class="btn btn-primary btn-lg font-semibold animate-glow" style="height: 48px; padding: 0 28px; border-radius: 8px;">
                <span>Create Your Free Account Now</span>
                <?= clay_icon('arrow-right', 16) ?>
            </a>
        </div>
    </section>
</div>
