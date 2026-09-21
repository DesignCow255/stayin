<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Analytics naming contract (§29).
 *
 * Namespace: `surface.entity.action`. This class is the single place event
 * names live, so instrumentation stays consistent and greppable. Delivery is
 * provider-agnostic: components only emit `data-analytics-*` attributes and
 * `assets/js/core/analytics.js` decides where the event goes (first-party sink,
 * and GA4 only if a measurement ID is configured). No vendor is hard-coded.
 */
final class Analytics
{
    /** @var array<string, string> event key => `surface.entity.action` */
    public const EVENTS = [
        'home_view' => 'home.page.view',
        'search_performed' => 'search.results.performed',
        'search_filter_applied' => 'search.filter.applied',
        'search_filter_removed' => 'search.filter.removed',
        'search_zero_results' => 'search.results.zero_results',
        'search_saved' => 'search.alert.created',
        'listing_view' => 'listing.property.view',
        'listing_save' => 'listing.property.save',
        'listing_unsave' => 'listing.property.unsave',
        'listing_share' => 'listing.property.share',
        'listing_gallery_open' => 'listing.gallery.open',
        'listing_amenity_expand' => 'listing.amenities.expand',
        'listing_map_click' => 'listing.location.map_open',
        'agent_contact' => 'agent.inquiry.submitted',
        'agent_whatsapp_click' => 'agent.whatsapp.click',
        'agent_call_click' => 'agent.phone.click',
        'viewing_requested' => 'viewing.booking.requested',
        'viewing_confirmed' => 'viewing.booking.confirmed',
        'booking_started' => 'booking.checkout.started',
        'booking_completed' => 'booking.checkout.completed',
        'booking_cancelled' => 'booking.checkout.cancelled',
        'listing_started' => 'listing.draft.started',
        'listing_draft_saved' => 'listing.draft.saved',
        'listing_published' => 'listing.property.published',
        'listing_submitted' => 'listing.property.submitted',
        'auth_signin' => 'auth.session.signin',
        'auth_signup' => 'auth.account.signup',
        'auth_signout' => 'auth.session.signout',
        'alert_created' => 'alert.search.created',
        'favourite_toggled' => 'listing.favourite.toggled',
        'compare_added' => 'compare.property.added',
        'newsletter_subscribed' => 'newsletter.subscription.created',
        'kyc_submitted' => 'kyc.document.submitted',
        'theme_toggled' => 'preferences.theme.toggled',
    ];

    /**
     * Build the `data-analytics-*` attributes consumed by the client dispatcher.
     *
     * @param array<string, string|int|float|null> $params
     */
    public static function attrs(string $event, array $params = []): string
    {
        $eventName = self::EVENTS[$event] ?? $event;
        $attributes = ' data-analytics="' . htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') . '"';

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attribute = 'data-analytics-' . preg_replace('/[^a-z0-9-]/', '', strtolower((string) $key));
            $attributes .= ' ' . $attribute . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return $attributes;
    }

    public static function name(string $event): string
    {
        return self::EVENTS[$event] ?? $event;
    }
}
