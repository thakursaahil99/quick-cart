<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Model\OrderHistory;

/**
 * Fictional shoppers and Indian delivery addresses for generated orders.
 * Emails use the reserved example.com domain; phone numbers follow the Indian mobile format.
 */
class People
{
    private const FIRST_NAMES = [
        'Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Ishaan', 'Rohan',
        'Ananya', 'Diya', 'Saanvi', 'Aadhya', 'Kavya', 'Isha', 'Priya', 'Neha', 'Pooja', 'Riya',
        'Karan', 'Nikhil', 'Rahul', 'Siddharth', 'Tanvi', 'Meghna', 'Farhan', 'Zoya', 'Harpreet', 'Gaurav',
    ];

    private const LAST_NAMES = [
        'Sharma', 'Patel', 'Reddy', 'Nair', 'Iyer', 'Gupta', 'Mehta', 'Kapoor', 'Singh', 'Das',
        'Menon', 'Joshi', 'Kulkarni', 'Banerjee', 'Chopra', 'Rao', 'Pillai', 'Khan', 'Bose', 'Agarwal',
    ];

    /**
     * City, region ID, region, PIN prefix (3 digits), street names.
     */
    private const CITIES = [
        ['Mumbai', 589, 'Maharashtra', '400', ['Linking Road, Bandra West', 'Powai Plaza, Hiranandani']],
        ['Pune', 589, 'Maharashtra', '411', ['FC Road, Shivajinagar', 'Baner Road, Baner']],
        ['Bengaluru', 585, 'Karnataka', '560', ['100 Feet Road, Indiranagar', 'Outer Ring Road, Bellandur']],
        ['New Delhi', 578, 'Delhi', '110', ['Lajpat Nagar II', 'Rajouri Garden Main Market']],
        ['Chennai', 599, 'Tamil Nadu', '600', ['Anna Salai, Teynampet', 'OMR, Thoraipakkam']],
        ['Hyderabad', 600, 'Telangana', '500', ['Road No. 36, Jubilee Hills', 'HITEC City, Madhapur']],
        ['Kolkata', 604, 'West Bengal', '700', ['Park Street', 'Salt Lake Sector V']],
        ['Ahmedabad', 580, 'Gujarat', '380', ['CG Road, Navrangpura', 'SG Highway, Bodakdev']],
        ['Jaipur', 597, 'Rajasthan', '302', ['MI Road', 'Malviya Nagar']],
        ['Kochi', 586, 'Kerala', '682', ['MG Road, Ernakulam', 'Kakkanad']],
        ['Lucknow', 602, 'Uttar Pradesh', '226', ['Hazratganj', 'Gomti Nagar']],
        ['Gurugram', 581, 'Haryana', '122', ['Golf Course Road, DLF Phase 5', 'Sohna Road']],
    ];

    /**
     * A shopper: name, example.com email and an address.
     *
     * @param callable(int, int): int $random Inclusive random integer generator
     * @return array{firstname: string, lastname: string, email: string, address: array}
     */
    public function create(callable $random): array
    {
        $first = self::FIRST_NAMES[$random(0, count(self::FIRST_NAMES) - 1)];
        $last = self::LAST_NAMES[$random(0, count(self::LAST_NAMES) - 1)];
        [$city, $regionId, $region, $pinPrefix, $streets] = self::CITIES[$random(0, count(self::CITIES) - 1)];

        return [
            'firstname' => $first,
            'lastname' => $last,
            'email' => strtolower($first . '.' . $last . $random(10, 99)) . '@example.com',
            'address' => [
                'firstname' => $first,
                'lastname' => $last,
                'street' => [$random(1, 220) . ', ' . $streets[$random(0, count($streets) - 1)]],
                'city' => $city,
                'region_id' => $regionId,
                'region' => $region,
                'postcode' => $pinPrefix . str_pad((string)$random(1, 99), 3, '0', STR_PAD_LEFT),
                'country_id' => 'IN',
                'telephone' => (string)$random(6, 9) . str_pad((string)$random(0, 999999999), 9, '0', STR_PAD_LEFT),
            ],
        ];
    }
}
