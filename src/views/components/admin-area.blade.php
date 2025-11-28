@php
    use Illuminate\Support\Facades\Auth;
    use Msdev2\Shopify\Utils;
@endphp

@php
    // Helper to decide if a user is admin/admin. Flexible checks: hasRole(), isAdmin(), role property.
    $show = false;
    if (Auth::check()) {
        $user = Auth::user();
        $allowed = false;
        // Check common role helpers
        if (method_exists($user, 'hasRole')) {
            $allowed = $user->hasRole('admin');
        }
        if (!$allowed && method_exists($user, 'isAdmin')) {
            $allowed = $user->isAdmin();
        }
        if (!$allowed && property_exists($user, 'role')) {
            $role = strtolower($user->role ?? '');
            $allowed = in_array($role, ['admin']);
        }
        if (!$allowed) {
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
