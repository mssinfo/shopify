@php
    use Illuminate\Support\Facades\Auth;
    use Msdev2\Shopify\Utils;
@endphp

@php
    // Helper to decide if a user is agent/admin. Flexible checks: hasRole(), isAdmin(), role property.
    $show = false;
    if (Auth::check()) {
        $user = Auth::user();
        $allowed = false;
        // Check common role helpers
        if (method_exists($user, 'hasRole')) {
            $allowed = $user->hasRole('agent') || $user->hasRole('admin');
        }
        if (!$allowed && method_exists($user, 'isAdmin')) {
            $allowed = $user->isAdmin();
        }
        if (!$allowed && property_exists($user, 'role')) {
            $role = strtolower($user->role ?? '');
            $allowed = in_array($role, ['agent', 'admin']);
        }
        if (!$allowed) {
            // fallback - if user model has 'is_agent' or 'is_admin' boolean attributes
            if (isset($user->is_agent) && $user->is_agent) $allowed = true;
            if (isset($user->is_admin) && $user->is_admin) $allowed = true;
        }

        // ensure not inside embedded iframe
        $notIframe = !Utils::shouldRedirectToEmbeddedApp() && request()->header('sec-fetch-dest') !== 'iframe';

        $show = $allowed && $notIframe;
    }

@endphp

@if ($show)
    {{-- Render wrapped content if any --}}
    <div class="msdev2-admin-slot">
        {{ $slot ?? '' }}
    </div>

@endif
