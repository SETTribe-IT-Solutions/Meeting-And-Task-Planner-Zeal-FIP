<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'organizer') {
        header('Location: dashboards/organizer.php');
        exit();
    }

    if ($_SESSION['role'] === 'collector') {
        header('Location: dashboards/collector.php');
        exit();
    }

    header('Location: dashboards/employee.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting & Task Planner</title>
    <meta name="description" content="Plan meetings, register employees, assign attendees, and track attendance in one workspace.">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .hero-bg {
            background-image:
                linear-gradient(90deg, rgba(8, 18, 30, 0.94) 0%, rgba(8, 18, 30, 0.80) 42%, rgba(8, 18, 30, 0.20) 76%),
                url('assets/images/landing-hero.png');
            background-position: center;
            background-size: cover;
        }

        @media (max-width: 860px) {
            .hero-bg {
                background-image:
                    linear-gradient(90deg, rgba(8, 18, 30, 0.95) 0%, rgba(8, 18, 30, 0.78) 100%),
                    url('assets/images/landing-hero.png');
                background-position: center right;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <header class="fixed inset-x-0 top-0 z-50 px-4 pt-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 rounded-lg border border-white/15 bg-slate-950/75 px-3 py-3 shadow-2xl shadow-slate-950/30 backdrop-blur-xl sm:px-5">
            <a href="index.php" class="group flex min-w-0 items-center gap-3 text-white no-underline" aria-label="Meeting and Task Planner home">
                <span class="relative flex h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-white/70">
                    <span class="absolute inset-x-0 top-0 h-4 bg-teal-600"></span>
                    <span class="absolute left-3 right-3 top-2 h-1 rounded bg-amber-300"></span>
                    <span class="grid w-full grid-cols-3 gap-1 self-end p-2 pt-5" aria-hidden="true">
                        <span class="h-2 rounded bg-slate-200"></span>
                        <span class="h-2 rounded bg-slate-200"></span>
                        <span class="h-2 rounded bg-teal-100"></span>
                        <span class="h-2 rounded bg-slate-200"></span>
                        <span class="h-2 rounded bg-amber-100"></span>
                        <span class="h-2 rounded bg-slate-200"></span>
                    </span>
                    <span class="absolute bottom-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-[10px] text-white ring-2 ring-white">
                        <i class="fas fa-check"></i>
                    </span>
                </span>
                <span class="grid min-w-0">
                    <span class="truncate text-base font-bold leading-tight sm:text-lg">Meeting & Task</span>
                    <span class="text-xs font-semibold uppercase text-white/65">Planner</span>
                </span>
            </a>

            <nav class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/10 p-1" aria-label="Primary navigation">
                <a href="#workflow" class="hidden min-h-10 items-center rounded-lg px-4 text-sm font-medium text-white/85 no-underline transition hover:bg-white/10 hover:text-white md:inline-flex">Workflow</a>
                <a href="#login" class="hidden min-h-10 items-center rounded-lg px-4 text-sm font-medium text-white/85 no-underline transition hover:bg-white/10 hover:text-white md:inline-flex">Roles</a>
                <a href="auth/login.php" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-amber-300 px-4 text-sm font-bold text-slate-950 no-underline shadow-lg shadow-amber-300/20 transition hover:-translate-y-0.5 hover:bg-amber-200 sm:px-5">
                    <i class="fas fa-right-to-bracket"></i>
                    <span class="hidden sm:inline">Login</span>
                </a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-bg relative flex min-h-[86vh] items-center overflow-hidden px-6 pb-16 pt-36 sm:px-8 lg:px-12">
            <div class="mx-auto w-full max-w-7xl">
                <div class="max-w-3xl text-white">
                    <div class="mb-5 inline-flex items-center gap-2 rounded-lg border border-amber-200/25 bg-amber-200/10 px-3 py-2 text-sm font-bold uppercase text-amber-200">
                        <i class="fas fa-circle-check"></i>
                        Faculty meeting coordination
                    </div>
                    <h1 class="max-w-4xl text-[clamp(2.7rem,7vw,5.8rem)] font-bold leading-none text-white">
                        Meeting & Task Planner
                    </h1>
                    <p class="mt-6 max-w-2xl text-base leading-8 text-white/85 sm:text-xl">
                        Plan meetings, register employees, assign attendees, and record attendance from one focused workspace built for daily academic coordination.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="auth/login.php" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-amber-300 px-6 font-bold text-slate-950 no-underline shadow-xl shadow-slate-950/20 transition hover:-translate-y-0.5 hover:bg-amber-200">
                            <i class="fas fa-right-to-bracket"></i>
                            Login to Dashboard
                        </a>
                        <a href="#workflow" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-white/35 bg-white/10 px-6 font-bold text-white no-underline transition hover:-translate-y-0.5 hover:bg-white/15">
                            <i class="fas fa-list-check"></i>
                            View Workflow
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-10 mx-auto -mt-9 grid w-[min(980px,calc(100%-2rem))] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 md:grid-cols-3" aria-label="Application highlights">
            <div class="border-b border-slate-200 p-6 md:border-b-0 md:border-r">
                <strong class="block text-2xl font-bold text-blue-700">Plan</strong>
                <span class="mt-1 block text-sm leading-6 text-slate-600">Schedule online or offline meetings with agenda details.</span>
            </div>
            <div class="border-b border-slate-200 p-6 md:border-b-0 md:border-r">
                <strong class="block text-2xl font-bold text-teal-700">Assign</strong>
                <span class="mt-1 block text-sm leading-6 text-slate-600">Add employees as attendees before each meeting.</span>
            </div>
            <div class="p-6">
                <strong class="block text-2xl font-bold text-emerald-700">Track</strong>
                <span class="mt-1 block text-sm leading-6 text-slate-600">Record attendance and review meeting reports.</span>
            </div>
        </section>

        <section class="px-6 py-16 sm:px-8 lg:px-12 lg:py-20" id="workflow">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-3xl">
                    <h2 class="text-[clamp(1.8rem,4vw,2.75rem)] font-bold leading-tight text-slate-950">A cleaner flow for every meeting</h2>
                    <p class="mt-3 leading-8 text-slate-600">Organizers, collectors, and employees enter through the same login and continue to the dashboard made for their role.</p>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-3">
                    <article class="rounded-lg border border-slate-200 bg-white p-7 shadow-sm">
                        <span class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-700 text-white">
                            <i class="fas fa-calendar-plus"></i>
                        </span>
                        <h3 class="text-lg font-bold text-slate-950">Create meeting schedules</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Set the meeting date, time, mode, department, agenda, location, and supporting attachments.</p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-white p-7 shadow-sm">
                        <span class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-teal-700 text-white">
                            <i class="fas fa-user-plus"></i>
                        </span>
                        <h3 class="text-lg font-bold text-slate-950">Register and assign employees</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Add new employees to the system and include them in the meeting attendee list immediately.</p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-white p-7 shadow-sm">
                        <span class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-700 text-white">
                            <i class="fas fa-clipboard-check"></i>
                        </span>
                        <h3 class="text-lg font-bold text-slate-950">Mark attendance</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Record present or absent status for assigned attendees and keep a report trail for follow-up.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="bg-slate-950 px-6 py-12 text-white sm:px-8 lg:px-12" id="login">
            <div class="mx-auto grid max-w-7xl items-center gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <h2 class="text-[clamp(1.7rem,4vw,2.5rem)] font-bold leading-tight">Login once, continue by role</h2>
                    <p class="mt-3 max-w-2xl leading-8 text-white/75">Use your assigned email and password. The system routes each user to the correct organizer, collector, or employee dashboard.</p>
                </div>
                <div class="grid gap-3">
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-white/15 bg-white/5 px-4 py-4">
                        <strong><i class="fas fa-user-tie mr-2 text-amber-200"></i> Organizer</strong>
                        <span class="text-sm text-white/70">Create meetings</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-white/15 bg-white/5 px-4 py-4">
                        <strong><i class="fas fa-clipboard-list mr-2 text-teal-200"></i> Collector</strong>
                        <span class="text-sm text-white/70">Coordinate records</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-white/15 bg-white/5 px-4 py-4">
                        <strong><i class="fas fa-user mr-2 text-emerald-200"></i> Employee</strong>
                        <span class="text-sm text-white/70">Access assignments</span>
                    </div>
                    <a href="auth/login.php" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-amber-300 px-6 font-bold text-slate-950 no-underline transition hover:-translate-y-0.5 hover:bg-amber-200">
                        <i class="fas fa-right-to-bracket"></i>
                        Continue to Login
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="flex flex-col gap-2 border-t border-slate-200 bg-white px-6 py-6 text-sm text-slate-600 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-12">
        <span>&copy; 2026 Meeting & Task Planner</span>
        <span>Built for meeting, attendance, and task coordination.</span>
    </footer>
</body>
</html>
