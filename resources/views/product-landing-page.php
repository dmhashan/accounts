<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Product Landing Page</title>

    <?= app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css', 'resources/js/app.js']) ?>

    <style>
        .landing-page-bg {
            background-color: #23262d;
        }

        .landing-page-text {
            color: #f5f7fa;
        }

        .landing-page-muted {
            color: #c8ced8;
        }

        .landing-page-border {
            border-color: #3a3f49;
        }

        .landing-page-accent {
            color: #e00b00;
        }

        .landing-page-accent-bg {
            background-color: #e00b00;
        }
    </style>
</head>

<body class="landing-page-bg landing-page-text font-sans antialiased">
    <!-- <div class="landing-page-border flex items-center justify-between border-b px-6 py-4">
        <div class="flex items-center gap-2">
            <img src="<?= asset('images/product-logo.svg') ?>" alt="Fitness Hub" class="h-7 w-auto">
        </div>
        <nav class="landing-page-muted hidden items-center gap-6 text-sm font-medium md:flex">
            <a href="#" class="hover:text-secondary-900">Home</a>
            <a href="#" class="hover:text-secondary-900">Features</a>
            <a href="#" class="hover:text-secondary-900">How it works</a>
            <a href="#" class="hover:text-secondary-900">About Us</a>
            <a href="#" class="hover:text-secondary-900">Testimonial</a>
            <a href="#" class="hover:text-secondary-900">Blog</a>
        </nav>
    </div> -->

    <div class="grid gap-8 p-6 lg:grid-cols-2 lg:items-center lg:p-10">
        <div>
            <img src="<?= asset('images/product-logo.svg') ?>" alt="Fitness Hub" class="h-12 w-auto">
            <h2 class="mb-4 text-4xl font-extrabold leading-tight sm:text-5xl">
                Your All-in-One
                <span class="landing-page-accent block">Management System</span>
            </h2>
            <p class="landing-page-muted mb-8 max-w-md text-sm leading-relaxed">
                Build routines, track progress, and manage your fitness journey in one place. Designed for high performance and a modern gym lifestyle.
            </p>
        </div>

        <div class="relative flex items-center justify-center gap-4 pt-4">
            <div class="h-[420px] w-[200px] rotate-[-14deg] overflow-hidden rounded-[2.5rem] border-8 border-secondary-900 bg-secondary-900 shadow-2xl">
                <img src="<?= asset('images/equipment/treadmill.jpg') ?>" alt="Fitness About" class="h-full w-full object-cover opacity-90">
            </div>

            <div class="relative z-10 h-[470px] w-[220px] overflow-hidden rounded-[2.7rem] border-8 border-secondary-900 bg-secondary-900 shadow-2xl">
                <img src="<?= asset('images/equipment/dumbbells.jpg') ?>" alt="Fitness Welcome" class="h-full w-full object-cover opacity-95">
                <div class="landing-page-accent-bg absolute bottom-4 left-4 right-4 rounded-full px-4 py-2 text-center text-sm font-semibold text-white">Login</div>
            </div>
        </div>
    </div>
</body>

</html>