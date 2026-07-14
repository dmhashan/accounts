<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Suspended - {{ $tenant->name }}</title>
    <!-- Use Tailwind CSS CDN for styling this standalone error page -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Background glowing decorative orbs -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Main Card -->
    <div class="relative bg-slate-900/50 backdrop-blur-2xl border border-slate-800/80 rounded-2xl p-10 max-w-lg w-full text-center shadow-2xl space-y-8">
        
        <!-- Beforward.lk Better Logo -->
        <div class="flex flex-col items-center gap-3">
          <div class="p-4 bg-rose-500/10 text-rose-500 rounded-full border border-rose-500/20 mb-2">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
          </div>
          
          <h2 class="text-3xl font-extrabold tracking-tight">Temporary Blocked</h2>
          <p class="text-slate-400 text-sm max-w-sm">This <span class="text-indigo-400 font-bold underline">{{ $appType }}</span> was temporarily blocked and is managed by <span class="text-slate-200 font-semibold">beforward.lk</span>.</p>
        </div>

        <div class="border-t border-slate-800/80 my-4"></div>

        <!-- System Information -->
        <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-6 text-left space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-semibold uppercase">Organization</span>
                <span class="text-slate-300 font-semibold">{{ $tenant->name }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-semibold uppercase">Subdomain</span>
                <span class="text-slate-300 font-mono font-semibold">{{ $tenant->domain }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-semibold uppercase">Status Code</span>
                <span class="px-2 py-0.5 bg-rose-500/10 text-rose-400 border border-rose-500/25 rounded font-mono font-bold">SUSPENDED_403</span>
            </div>
        </div>

        <!-- Footer / Managed By Beforward Logo -->
        <div class="flex flex-col items-center gap-2 text-xs text-slate-500 pt-4">
            <span>Managed & Provisioned by</span>
            <a href="https://beforward.lk" target="_blank" class="flex items-center gap-1.5 text-indigo-400 font-bold hover:underline transition-all">
                <!-- Premium Beforward Logo (Forward Arrow) -->
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                </svg>
                <span>beforward.lk</span>
            </a>
        </div>
    </div>
</body>
</html>
