<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api\Request;

/**
 * Pointy sign up constant
 */
interface PointySignupInterface
{
    const PARTNER_REFERENCE_ID = 'partner_reference_id';

    const SHOP = 'shop';
    const SHOP_NAME = 'name';
    const SHOP_PHONE_NUMBER = 'phone_number';

    const LOCATION = 'location';
    const LAT = 'lat';
    const LON = 'lon';
    const ADDRESS = 'address';
    const COUNTRY = 'country';

    const OPENING_HOURS = 'opening_hours';
    const OPENING_MINUTES = 'opening_mins';
    const CLOSING_MINUTES = 'closing_mins';
    const MONDAY = 'mon';
    const TUESDAY = 'tue';
    const WEDNESDAY = 'wed';
    const THURSDAY = 'thu';
    const FRIDAY = 'fri';
    const SATURDAY = 'sat';
    const SUNDAY = 'sun';

    const OWNER = 'owner';
    const OWNER_NAME = 'name';
    const OWNER_EMAIL = 'email';
    const OWNER_PHONE_NUMBER = 'phone_number';

    const UTM_CAMPAIGN = 'utm_campaign';
}
