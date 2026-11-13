/**
 * QuickCart demo catalogue - single source of truth for the DemoData fixtures.
 * Brands are fictional. Prices are INR (the store's base currency).
 */

export const BRANDS = ['Voltix', 'Nexora', 'Aurion', 'Pixelon', 'Sonavo', 'Zentra', 'Orbix', 'Kairo'];

export const RAM = ['4 GB', '6 GB', '8 GB', '12 GB', '16 GB', '32 GB'];
export const STORAGE = ['64 GB', '128 GB', '256 GB', '512 GB', '1 TB', '2 TB'];
export const CONNECTIVITY = ['5G', 'Wi-Fi 6E', 'Wi-Fi 7', 'Bluetooth 5.3', 'Bluetooth 5.4', 'NFC', 'USB-C', 'GPS'];
export const WARRANTY = ['6 Months', '1 Year', '2 Years'];

/** Top-level categories (menu order) with their children. `art` = device used for the tile image. */
export const CATEGORIES = [
    {
        key: 'mobiles', name: 'Mobiles & Tablets', art: 'smartphone',
        description: 'Flagship smartphones and tablets with pro-grade cameras, all-day batteries and blazing-fast 5G.',
        children: [
            { key: 'smartphones', name: 'Smartphones' },
            { key: 'tablets', name: 'Tablets' },
        ],
    },
    {
        key: 'laptops', name: 'Laptops & Monitors', art: 'laptop',
        description: 'Ultra-thin notebooks, creator workstations, gaming rigs and the displays to match.',
        children: [
            { key: 'notebooks', name: 'Laptops' },
            { key: 'monitors', name: 'Monitors' },
        ],
    },
    {
        key: 'audio', name: 'Audio', art: 'headphones',
        description: 'Noise-cancelling headphones, true wireless earbuds and room-filling speakers.',
        children: [
            { key: 'headphones', name: 'Headphones' },
            { key: 'earbuds', name: 'Earbuds' },
            { key: 'speakers', name: 'Speakers' },
        ],
    },
    {
        key: 'wearables', name: 'Wearables', art: 'smartwatch',
        description: 'Smartwatches and fitness bands that track every beat, step and night of sleep.',
        children: [
            { key: 'smartwatches', name: 'Smartwatches' },
            { key: 'fitness-bands', name: 'Fitness Bands' },
        ],
    },
    {
        key: 'cameras', name: 'Cameras & Drones', art: 'camera',
        description: 'Mirrorless cameras, vlogging kits, action cams and 4K drones for every story.',
        children: [
            { key: 'digital-cameras', name: 'Cameras' },
            { key: 'drones', name: 'Drones' },
        ],
    },
    {
        key: 'gaming', name: 'Gaming', art: 'controller',
        description: 'Next-gen consoles and pro controllers built for low latency and long sessions.',
        children: [
            { key: 'consoles', name: 'Consoles' },
            { key: 'controllers', name: 'Controllers' },
        ],
    },
    {
        key: 'accessories', name: 'Accessories', art: 'keyboard',
        description: 'Keyboards, mice and power banks that make every setup faster.',
        children: [
            { key: 'keyboards-mice', name: 'Keyboards & Mice' },
            { key: 'power-charging', name: 'Power & Charging' },
        ],
    },
    {
        key: 'deals', name: 'Deals', art: 'tag',
        description: 'Limited-time prices on bestselling tech. Updated every week.',
        children: [],
    },
];

/** Closing paragraph per leaf category, appended to every product description. */
export const CLOSINGS = {
    smartphones: 'Ships with a USB-C cable and SIM tool, and is backed by QuickCart\'s 7-day replacement promise.',
    tablets: 'Pairs with the matching stylus and keyboard cover (sold separately) to turn into a portable studio.',
    notebooks: 'Comes with a fast USB-C charger and the latest OS pre-installed, so you can get to work in minutes.',
    monitors: 'Includes HDMI and USB-C cables plus a VESA mount adapter for clean desk setups.',
    headphones: 'Folds flat into the included hard-shell travel case.',
    earbuds: 'Includes three sizes of silicone ear tips for a secure, comfortable seal.',
    speakers: 'Pair two units for true stereo sound with a single tap in the companion app.',
    smartwatches: 'Works with Android and iOS and syncs to the companion app for detailed health insights.',
    'fitness-bands': 'Water resistant to 5 ATM, so you can track swims as accurately as runs.',
    'digital-cameras': 'Kit includes a rechargeable battery, shoulder strap and USB-C charging cable.',
    drones: 'Register your drone and follow local flying regulations before every flight.',
    consoles: 'Includes one wireless controller, HDMI 2.1 cable and power cable.',
    controllers: 'Charges over USB-C and remembers up to three paired devices.',
    'keyboards-mice': 'Connects over 2.4 GHz receiver or Bluetooth and switches between devices instantly.',
    'power-charging': 'Built-in protection against over-charge, short-circuit and overheating keeps your devices safe.',
};

const p = (o) => o;

export const PRODUCTS = [
    // ---------------------------------------------------------------- Smartphones
    p({ sku: 'QC-PH-EDGE5PRO', name: 'Nexora Edge 5 Pro', type: 'configurable', device: 'smartphone', category: 'smartphones', brand: 'Nexora', price: 69999,
        colors: ['midnight', 'blue', 'silver'], ram: '12 GB', storage: '256 GB', display: '6.7-inch LTPO AMOLED, 120 Hz', battery: '5,000 mAh, 80 W fast charge',
        connectivity: ['5G', 'Wi-Fi 7', 'Bluetooth 5.4', 'NFC', 'USB-C'], warranty: '1 Year', trending: true, isNew: true, weight: 0.21,
        short: 'A titanium-framed flagship with a 200 MP pro camera system and an AI chip that makes every shot look studio-lit.',
        highlights: ['200 MP main camera with 5x periscope zoom', 'LTPO AMOLED display that peaks at 3,000 nits', '80 W wired charging: 0 to 100% in 32 minutes'] }),
    p({ sku: 'QC-PH-EDGE5', name: 'Nexora Edge 5', type: 'simple', device: 'smartphone', category: 'smartphones', brand: 'Nexora', price: 49999, special: 44999,
        color: 'violet', ram: '8 GB', storage: '128 GB', display: '6.4-inch AMOLED, 120 Hz', battery: '4,700 mAh, 45 W fast charge',
        connectivity: ['5G', 'Wi-Fi 6E', 'Bluetooth 5.3', 'NFC', 'USB-C'], warranty: '1 Year', weight: 0.19,
        short: 'All the essentials of the Edge 5 Pro in a lighter, more pocketable design.',
        highlights: ['50 MP camera with optical image stabilisation', 'IP68 dust and water resistance', 'Four years of OS updates'] }),
    p({ sku: 'QC-PH-NOVAX', name: 'Voltix Nova X', type: 'simple', device: 'smartphone', category: 'smartphones', brand: 'Voltix', price: 29999, special: 26499,
        color: 'teal', ram: '8 GB', storage: '256 GB', display: '6.6-inch AMOLED, 120 Hz', battery: '5,500 mAh, 67 W turbo charge',
        connectivity: ['5G', 'Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 0.2,
        short: 'Mid-range price, flagship feel: a huge battery and a buttery 120 Hz screen.',
        highlights: ['Two-day battery life with 67 W turbo charging', '108 MP triple camera', 'Stereo speakers tuned for gaming'] }),
    p({ sku: 'QC-PH-ONELITE', name: 'Aurion One Lite', type: 'simple', device: 'smartphone', category: 'smartphones', brand: 'Aurion', price: 14999,
        color: 'gold', ram: '6 GB', storage: '128 GB', display: '6.5-inch LCD, 90 Hz', battery: '6,000 mAh',
        connectivity: ['5G', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.2,
        short: 'An affordable 5G phone with a battery that easily lasts two days.',
        highlights: ['6,000 mAh battery', '50 MP AI dual camera', 'Side-mounted fingerprint sensor'] }),
    p({ sku: 'QC-PH-P9PRO', name: 'Pixelon P9 Pro', type: 'simple', device: 'smartphone', category: 'smartphones', brand: 'Pixelon', price: 84999,
        color: 'midnight', ram: '16 GB', storage: '512 GB', display: '6.8-inch QHD+ AMOLED, 120 Hz', battery: '5,100 mAh, 65 W',
        connectivity: ['5G', 'Wi-Fi 7', 'Bluetooth 5.4', 'NFC', 'USB-C'], warranty: '2 Years', trending: true, isNew: true, weight: 0.22,
        short: 'A camera-first flagship co-engineered with Pixelon\'s imaging lab.',
        highlights: ['1-inch type main sensor with variable aperture', '8K video and pro RAW capture', 'Night mode that sees in near darkness'] }),

    // ---------------------------------------------------------------- Tablets
    p({ sku: 'QC-TB-TABS10', name: 'Nexora Tab S10 11"', type: 'simple', device: 'tablet', category: 'tablets', brand: 'Nexora', price: 42999, special: 38999,
        color: 'silver', ram: '8 GB', storage: '256 GB', display: '11-inch 2.5K LCD, 120 Hz', battery: '8,000 mAh',
        connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.5,
        short: 'A sleek 11-inch tablet for streaming, sketching and split-screen productivity.',
        highlights: ['Quad speakers with spatial audio', 'Stylus support with 4,096 pressure levels', 'Desktop mode for multi-window work'] }),
    p({ sku: 'QC-TB-PADAIR', name: 'Aurion Pad Air', type: 'simple', device: 'tablet', category: 'tablets', brand: 'Aurion', price: 24999,
        color: 'blue', ram: '6 GB', storage: '128 GB', display: '10.9-inch LCD, 90 Hz', battery: '7,600 mAh',
        connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', isNew: true, weight: 0.47,
        short: 'A light, colourful tablet that is perfect for classes, comics and video calls.',
        highlights: ['Kids mode with parental controls', 'Landscape front camera for calls', 'Up to 14 hours of video playback'] }),
    p({ sku: 'QC-TB-SLATEPRO', name: 'Orbix Slate Pro 13', type: 'simple', device: 'tablet', category: 'tablets', brand: 'Orbix', price: 89999,
        color: 'midnight', ram: '16 GB', storage: '512 GB', display: '13-inch OLED, 144 Hz', battery: '10,090 mAh',
        connectivity: ['5G', 'Wi-Fi 7', 'Bluetooth 5.4', 'USB-C'], warranty: '2 Years', trending: true, weight: 0.62,
        short: 'A 13-inch OLED powerhouse that replaces a laptop for creators on the move.',
        highlights: ['Tandem OLED panel with 1,600 nits HDR', 'High-speed USB-C for external displays', 'Magnetic keyboard and pen support'] }),

    // ---------------------------------------------------------------- Laptops
    p({ sku: 'QC-LT-AIR13', name: 'Orbix Air 13', type: 'configurable', device: 'laptop', category: 'notebooks', brand: 'Orbix', price: 79990,
        colors: ['silver', 'midnight', 'gold'], ram: '16 GB', storage: '512 GB', display: '13.6-inch 2.8K OLED', battery: 'Up to 18 hours',
        connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 1.18,
        short: 'An impossibly thin aluminium notebook with a stunning OLED display and silent, fanless design.',
        highlights: ['1.18 kg aluminium unibody', 'Fanless design: completely silent', 'Up to 18 hours of battery life'] }),
    p({ sku: 'QC-LT-PRO16', name: 'Orbix Pro 16 Creator', type: 'simple', device: 'laptop', category: 'notebooks', brand: 'Orbix', price: 189990, special: 174990,
        color: 'silver', ram: '32 GB', storage: '1 TB', display: '16-inch 3.2K Mini-LED, 165 Hz', battery: '99.9 Wh',
        connectivity: ['Wi-Fi 7', 'Bluetooth 5.4', 'USB-C'], warranty: '2 Years', isNew: true, weight: 2.1,
        short: 'A colour-accurate Mini-LED workstation for 8K editing, 3D and AI workloads.',
        highlights: ['Factory-calibrated Delta E < 1 display', 'Dedicated high-performance graphics', 'Three USB-C ports, HDMI 2.1 and SD Express'] }),
    p({ sku: 'QC-LT-BLADE15', name: 'Zentra Blade 15 Gaming', type: 'simple', device: 'laptop', category: 'notebooks', brand: 'Zentra', price: 129990,
        color: 'midnight', ram: '16 GB', storage: '1 TB', display: '15.6-inch QHD, 240 Hz', battery: '90 Wh',
        connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 2.0,
        short: 'A 240 Hz esports laptop with vapour-chamber cooling and per-key RGB.',
        highlights: ['240 Hz QHD panel with 3 ms response', 'Vapour-chamber cooling keeps clocks high', 'Per-key RGB keyboard'] }),
    p({ sku: 'QC-LT-BOOK14', name: 'Aurion Book 14', type: 'simple', device: 'laptop', category: 'notebooks', brand: 'Aurion', price: 54990, special: 49990,
        color: 'silver', ram: '16 GB', storage: '512 GB', display: '14-inch FHD+ IPS', battery: 'Up to 12 hours',
        connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 1.4,
        short: 'A dependable everyday laptop for study, work-from-home and streaming.',
        highlights: ['16:10 display for more vertical space', 'Backlit keyboard and fingerprint login', 'Fast charging: 50% in 30 minutes'] }),
    p({ sku: 'QC-LT-FLEX14', name: 'Voltix Flex 14 2-in-1', type: 'simple', device: 'laptop', category: 'notebooks', brand: 'Voltix', price: 64990,
        color: 'violet', ram: '16 GB', storage: '512 GB', display: '14-inch touch OLED', battery: 'Up to 15 hours',
        connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', isNew: true, weight: 1.35,
        short: 'A convertible OLED laptop that flips into a tablet for notes and sketches.',
        highlights: ['360 degree hinge with four modes', 'Active pen included', 'Vivid OLED touch display'] }),

    // ---------------------------------------------------------------- Monitors
    p({ sku: 'QC-MN-UV27', name: 'Pixelon UltraView 27 4K', type: 'simple', device: 'monitor', category: 'monitors', brand: 'Pixelon', price: 32999, special: 28999,
        color: 'silver', display: '27-inch 4K IPS, 144 Hz', connectivity: ['USB-C'], warranty: '2 Years', weight: 5.8,
        short: 'A sharp 4K, 144 Hz monitor that is equally good for design work and gaming.',
        highlights: ['98% DCI-P3 colour coverage', '90 W USB-C power delivery', 'Height, tilt and pivot adjustable stand'] }),
    p({ sku: 'QC-MN-ARENA32', name: 'Zentra Arena 32 Curved QHD', type: 'simple', device: 'monitor', category: 'monitors', brand: 'Zentra', price: 27499,
        color: 'midnight', display: '32-inch QHD curved VA, 165 Hz', connectivity: ['USB-C'], warranty: '1 Year', trending: true, weight: 6.4,
        short: 'An immersive 1500R curved gaming monitor with silky 165 Hz motion.',
        highlights: ['1500R curvature for immersion', '1 ms MPRT with adaptive sync', 'Built-in crosshair and FPS counter'] }),
    p({ sku: 'QC-MN-STUDIO24', name: 'Pixelon Studio 24', type: 'simple', device: 'monitor', category: 'monitors', brand: 'Pixelon', price: 13999,
        color: 'white', display: '24-inch FHD IPS, 100 Hz', connectivity: ['USB-C'], warranty: '1 Year', weight: 3.9,
        short: 'A slim, bezel-less 24-inch display that looks great on any desk.',
        highlights: ['Three-side borderless design', 'Flicker-free with low blue light', 'Wall-mountable'] }),

    // ---------------------------------------------------------------- Headphones
    p({ sku: 'QC-HP-AURA', name: 'Sonavo Aura ANC Headphones', type: 'configurable', device: 'headphones', category: 'headphones', brand: 'Sonavo', price: 24990,
        colors: ['midnight', 'silver', 'blue'], battery: 'Up to 40 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 0.25,
        short: 'Industry-leading noise cancellation with eight microphones and adaptive sound.',
        highlights: ['Adaptive ANC with eight microphones', 'Hi-Res wireless audio with a high-bitrate codec', '40-hour battery; 3 minutes of charge gives 3 hours of play'] }),
    p({ sku: 'QC-HP-STUDIO2', name: 'Sonavo Studio Monitor 2', type: 'simple', device: 'headphones', category: 'headphones', brand: 'Sonavo', price: 12990, special: 10990,
        color: 'silver', connectivity: ['USB-C'], warranty: '1 Year', weight: 0.29,
        short: 'Reference-grade wired headphones for mixing, mastering and critical listening.',
        highlights: ['50 mm neodymium drivers with a flat response', 'Detachable coiled cable', 'Swivelling cups for one-ear monitoring'] }),
    p({ sku: 'QC-HP-BEATMAX', name: 'Voltix Beat Max', type: 'simple', device: 'headphones', category: 'headphones', brand: 'Voltix', price: 8999, special: 6999,
        color: 'red', battery: 'Up to 60 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 0.24,
        short: 'Big bass, bold colours and a battery that lasts more than two days of listening.',
        highlights: ['60-hour battery life', 'Bass boost mode', 'Low-latency game mode'] }),
    p({ sku: 'QC-HP-KIDSAFE', name: 'Kairo Kids Safe Headphones', type: 'simple', device: 'headphones', category: 'headphones', brand: 'Kairo', price: 2499,
        color: 'teal', battery: 'Up to 30 hours', connectivity: ['Bluetooth 5.3'], warranty: '6 Months', weight: 0.18,
        short: 'Volume-limited, bendable headphones designed for little ears.',
        highlights: ['85 dB volume limiter', 'Bendable, near-unbreakable headband', 'Built-in microphone for online classes'] }),

    // ---------------------------------------------------------------- Earbuds
    p({ sku: 'QC-EB-BUDSPRO2', name: 'Sonavo Buds Pro 2', type: 'simple', device: 'earbuds', category: 'earbuds', brand: 'Sonavo', price: 14990, special: 12990,
        color: 'white', battery: 'Up to 30 hours with case', connectivity: ['Bluetooth 5.4', 'USB-C'], warranty: '1 Year', trending: true, isNew: true, weight: 0.05,
        short: 'Premium true wireless earbuds with adaptive ANC and personalised spatial audio.',
        highlights: ['Adaptive noise cancellation', 'Wireless charging case', 'IPX4 sweat resistance'] }),
    p({ sku: 'QC-EB-AIRDOTS', name: 'Voltix Air Dots', type: 'simple', device: 'earbuds', category: 'earbuds', brand: 'Voltix', price: 2999, special: 2299,
        color: 'midnight', battery: 'Up to 36 hours with case', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.04,
        short: 'Everyday earbuds with clear calls and quick pairing.',
        highlights: ['Quad-mic environmental noise cancellation for calls', '10-minute charge gives 2 hours of play', 'Touch controls'] }),
    p({ sku: 'QC-EB-FITBUDS', name: 'Kairo FitBuds Sport', type: 'simple', device: 'earbuds', category: 'earbuds', brand: 'Kairo', price: 4499,
        color: 'sage', battery: 'Up to 28 hours with case', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.05,
        short: 'Secure-fit sport earbuds that stay put through every rep and run.',
        highlights: ['Ear-wing design for a secure fit', 'IP55 dust and sweat proof', 'Ambient mode for outdoor runs'] }),

    // ---------------------------------------------------------------- Speakers
    p({ sku: 'QC-SP-BOOM360', name: 'Sonavo Boom 360', type: 'simple', device: 'speaker', category: 'speakers', brand: 'Sonavo', price: 9990,
        color: 'blue', battery: 'Up to 24 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 0.68,
        short: 'A rugged 360-degree speaker that floats, survives drops and parties all day.',
        highlights: ['IP67 waterproof and it floats', '360-degree sound with deep bass', 'Party mode links up to 100 speakers'] }),
    p({ sku: 'QC-SP-PEBBLE', name: 'Kairo Pebble Mini', type: 'simple', device: 'speaker', category: 'speakers', brand: 'Kairo', price: 2999, special: 2499,
        color: 'rose', battery: 'Up to 12 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '6 Months', weight: 0.3,
        short: 'A pocket-sized speaker with surprisingly big sound.',
        highlights: ['Clip-on strap for bags and bikes', 'Built-in speakerphone', 'IPX5 splash proof'] }),
    p({ sku: 'QC-SP-HOMEMAX', name: 'Sonavo Home Max', type: 'simple', device: 'speaker', category: 'speakers', brand: 'Sonavo', price: 29990,
        color: 'midnight', connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3'], warranty: '2 Years', isNew: true, weight: 3.1,
        short: 'A room-filling smart home speaker with automatic room calibration.',
        highlights: ['Seven drivers with automatic room tuning', 'Multi-room audio over Wi-Fi', 'Voice assistant built in'] }),

    // ---------------------------------------------------------------- Smartwatches
    p({ sku: 'QC-SW-PULSE3', name: 'Voltix Pulse Watch 3', type: 'configurable', device: 'smartwatch', category: 'smartwatches', brand: 'Voltix', price: 19999,
        colors: ['midnight', 'rose', 'sage'], display: '1.9-inch AMOLED, always-on', battery: 'Up to 7 days', connectivity: ['Bluetooth 5.3', 'NFC', 'GPS'],
        warranty: '1 Year', trending: true, isNew: true, weight: 0.05,
        short: 'A health-first smartwatch with ECG, SpO2 and dual-band GPS.',
        highlights: ['ECG and blood-oxygen monitoring', 'Dual-band GPS for accurate runs', 'Tap-to-pay with NFC'] }),
    p({ sku: 'QC-SW-ULTRA', name: 'Nexora Watch Ultra', type: 'simple', device: 'smartwatch', category: 'smartwatches', brand: 'Nexora', price: 44999,
        color: 'gold', display: '2.0-inch sapphire AMOLED', battery: 'Up to 4 days', connectivity: ['5G', 'Bluetooth 5.4', 'NFC', 'GPS'], warranty: '2 Years', weight: 0.06,
        short: 'A titanium adventure watch with cellular connectivity and a 3,000-nit display.',
        highlights: ['Grade 5 titanium case', 'eSIM: calls without your phone', 'Dive-ready to 100 m'] }),
    p({ sku: 'QC-SW-LITE', name: 'Aurion Watch Lite', type: 'simple', device: 'smartwatch', category: 'smartwatches', brand: 'Aurion', price: 4999, special: 3999,
        color: 'teal', display: '1.8-inch AMOLED', battery: 'Up to 10 days', connectivity: ['Bluetooth 5.3'], warranty: '1 Year', weight: 0.04,
        short: 'An affordable smartwatch with Bluetooth calling and 100+ sports modes.',
        highlights: ['Bluetooth calling from your wrist', '100+ sports modes', '10-day battery life'] }),
    p({ sku: 'QC-SW-ACTIVEGT', name: 'Zentra Active GT', type: 'simple', device: 'smartwatch', category: 'smartwatches', brand: 'Zentra', price: 12999,
        color: 'red', display: '1.9-inch AMOLED', battery: 'Up to 14 days', connectivity: ['Bluetooth 5.3', 'GPS'], warranty: '1 Year', weight: 0.05,
        short: 'A training watch with recovery insights and up to two weeks of battery life.',
        highlights: ['Training load and recovery advisor', 'Built-in GPS with route navigation', '14-day battery life'] }),

    // ---------------------------------------------------------------- Fitness bands
    p({ sku: 'QC-FB-BAND8', name: 'Voltix Band 8', type: 'simple', device: 'fitnessband', category: 'fitness-bands', brand: 'Voltix', price: 2999, special: 2499,
        color: 'midnight', display: '1.6-inch AMOLED', battery: 'Up to 16 days', connectivity: ['Bluetooth 5.3'], warranty: '1 Year', weight: 0.03,
        short: 'A featherweight band with 24/7 heart-rate and sleep tracking.',
        highlights: ['16-day battery life', '150+ workout modes', 'Sleep score with breathing quality'] }),
    p({ sku: 'QC-FB-FITPRO', name: 'Aurion Fit Pro', type: 'simple', device: 'fitnessband', category: 'fitness-bands', brand: 'Aurion', price: 4499,
        color: 'violet', display: '1.7-inch AMOLED', battery: 'Up to 12 days', connectivity: ['Bluetooth 5.3', 'GPS'], warranty: '1 Year', isNew: true, weight: 0.03,
        short: 'A fitness band with built-in GPS for phone-free runs.',
        highlights: ['Built-in GPS', 'Stress and SpO2 monitoring', '5 ATM water resistance'] }),

    // ---------------------------------------------------------------- Cameras
    p({ sku: 'QC-CM-ALPHAM50', name: 'Pixelon Alpha M50 Mirrorless', type: 'simple', device: 'camera', category: 'digital-cameras', brand: 'Pixelon', price: 114990, special: 104990,
        color: 'midnight', battery: 'Up to 580 shots', connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '2 Years', trending: true, weight: 0.65,
        short: 'A 33 MP full-frame mirrorless camera with AI subject tracking.',
        highlights: ['33 MP full-frame sensor', 'AI autofocus tracks eyes, animals and vehicles', '4K 60p 10-bit video'] }),
    p({ sku: 'QC-CM-ZVKIT', name: 'Pixelon ZV Vlog Kit', type: 'simple', device: 'camera', category: 'digital-cameras', brand: 'Pixelon', price: 64990,
        color: 'silver', battery: 'Up to 440 shots', connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', isNew: true, weight: 0.34,
        short: 'A creator kit with a flip screen, directional mic and grip tripod.',
        highlights: ['Vari-angle flip screen', 'Directional three-capsule microphone', 'Product showcase autofocus mode'] }),
    p({ sku: 'QC-CM-ACTION4K', name: 'Kairo ActionCam 4K', type: 'simple', device: 'camera', category: 'digital-cameras', brand: 'Kairo', price: 19990, special: 16990,
        color: 'blue', battery: 'Up to 150 minutes', connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.15,
        short: 'A rugged, waterproof action camera with horizon-locked stabilisation.',
        highlights: ['4K 120 fps slow motion', 'Horizon lock stabilisation', 'Waterproof to 10 m without a case'] }),

    // ---------------------------------------------------------------- Drones
    p({ sku: 'QC-DR-SKYLITE', name: 'Orbix SkyLite Mini', type: 'simple', device: 'drone', category: 'drones', brand: 'Orbix', price: 42990,
        color: 'white', battery: 'Up to 34 minutes of flight', connectivity: ['Wi-Fi 6E', 'GPS', 'USB-C'], warranty: '1 Year', weight: 0.249,
        short: 'A sub-250 g drone that folds into your palm and shoots 4K HDR.',
        highlights: ['Under 250 g for easy travel', '4K HDR video with vertical shooting', '34-minute flight time'] }),
    p({ sku: 'QC-DR-SKYPRO', name: 'Orbix SkyPro 4K', type: 'simple', device: 'drone', category: 'drones', brand: 'Orbix', price: 89990, special: 82990,
        color: 'silver', battery: 'Up to 46 minutes of flight', connectivity: ['Wi-Fi 7', 'GPS', 'USB-C'], warranty: '2 Years', trending: true, weight: 0.9,
        short: 'A pro drone with omnidirectional obstacle sensing and a dual-camera gimbal.',
        highlights: ['Omnidirectional obstacle sensing', 'Dual camera with 7x tele zoom', '20 km 1080p live transmission'] }),

    // ---------------------------------------------------------------- Consoles
    p({ sku: 'QC-GM-NEXUSX', name: 'Zentra Nexus X Console', type: 'simple', device: 'console', category: 'consoles', brand: 'Zentra', price: 49990,
        color: 'midnight', storage: '1 TB', connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 4.4,
        short: 'A next-gen console with 4K 120 fps gaming and lightning-fast loading.',
        highlights: ['4K at 120 fps with ray tracing', 'Custom NVMe SSD for instant loading', 'Quick resume across multiple games'] }),
    p({ sku: 'QC-GM-NEXUSS', name: 'Zentra Nexus S Digital', type: 'simple', device: 'console', category: 'consoles', brand: 'Zentra', price: 34990, special: 31990,
        color: 'white', storage: '512 GB', connectivity: ['Wi-Fi 6E', 'Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 1.9,
        short: 'An all-digital, compact console with the full Nexus game library.',
        highlights: ['1440p at up to 120 fps', 'Compact all-digital design', 'Cloud saves and game sharing'] }),

    // ---------------------------------------------------------------- Controllers
    p({ sku: 'QC-GM-WIRELESS', name: 'Zentra Nexus Wireless Controller', type: 'simple', device: 'controller', category: 'controllers', brand: 'Zentra', price: 5990,
        color: 'blue', battery: 'Up to 40 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.28,
        short: 'The official Nexus controller with textured grips and haptic feedback.',
        highlights: ['Haptic feedback and adaptive triggers', 'Textured grips', 'Works with PC and mobile'] }),
    p({ sku: 'QC-GM-ELITE', name: 'Zentra Elite Pro Controller', type: 'simple', device: 'controller', category: 'controllers', brand: 'Zentra', price: 14990,
        color: 'midnight', battery: 'Up to 40 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', isNew: true, weight: 0.34,
        short: 'A pro controller with swappable sticks, back paddles and trigger stops.',
        highlights: ['Four rear paddles', 'Adjustable-tension thumbsticks', 'Hair-trigger locks'] }),
    p({ sku: 'QC-GM-ARCADE', name: 'Kairo Arcade Pad', type: 'simple', device: 'controller', category: 'controllers', brand: 'Kairo', price: 1999, special: 1599,
        color: 'red', battery: 'Up to 20 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '6 Months', weight: 0.22,
        short: 'A fun, affordable wireless gamepad for phones, tablets and PCs.',
        highlights: ['Phone clip included', 'Turbo button', 'Works with Android, iOS and PC'] }),

    // ---------------------------------------------------------------- Keyboards & mice
    p({ sku: 'QC-AC-MECH75', name: 'Voltix MechType 75 Wireless Keyboard', type: 'simple', device: 'keyboard', category: 'keyboards-mice', brand: 'Voltix', price: 7999, special: 6499,
        color: 'silver', battery: 'Up to 200 hours', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', trending: true, weight: 0.85,
        short: 'A gasket-mounted 75% mechanical keyboard with hot-swappable switches.',
        highlights: ['Hot-swappable linear switches', 'Gasket mount for a soft, quiet feel', 'Aluminium volume knob'] }),
    p({ sku: 'QC-AC-STRIKE', name: 'Zentra Strike RGB Keyboard', type: 'simple', device: 'keyboard', category: 'keyboards-mice', brand: 'Zentra', price: 9999,
        color: 'midnight', connectivity: ['USB-C'], warranty: '2 Years', weight: 1.1,
        short: 'A full RGB gaming keyboard with optical switches and 8,000 Hz polling.',
        highlights: ['Optical switches with 0.2 ms actuation', '8,000 Hz polling rate', 'Per-key RGB with on-board memory'] }),
    p({ sku: 'QC-AC-GLIDEPRO', name: 'Voltix Glide Pro Mouse', type: 'simple', device: 'mouse', category: 'keyboards-mice', brand: 'Voltix', price: 4999,
        color: 'midnight', battery: 'Up to 70 days', connectivity: ['Bluetooth 5.3', 'USB-C'], warranty: '1 Year', weight: 0.14,
        short: 'An ergonomic productivity mouse with an electromagnetic scroll wheel.',
        highlights: ['Ultra-fast electromagnetic scroll', 'Tracks on glass', 'Switches between three devices'] }),
    p({ sku: 'QC-AC-SILENT', name: 'Aurion Silent Mouse', type: 'simple', device: 'mouse', category: 'keyboards-mice', brand: 'Aurion', price: 1299, special: 999,
        color: 'rose', battery: 'Up to 18 months', connectivity: ['Bluetooth 5.3'], warranty: '1 Year', weight: 0.08,
        short: 'A compact wireless mouse with 90% quieter clicks.',
        highlights: ['Silent click switches', '18-month battery life', 'Compact and travel-friendly'] }),

    // ---------------------------------------------------------------- Power & charging
    p({ sku: 'QC-AC-POWER20K', name: 'Voltix PowerCore 20K', type: 'simple', device: 'powerbank', category: 'power-charging', brand: 'Voltix', price: 2999, special: 2499,
        color: 'midnight', battery: '20,000 mAh, 65 W', connectivity: ['USB-C'], warranty: '1 Year', trending: true, weight: 0.42,
        short: 'A 20,000 mAh power bank with 65 W output that can charge a laptop.',
        highlights: ['65 W USB-C: charges laptops', 'Charges three devices at once', 'Smart LED battery indicator'] }),
    p({ sku: 'QC-AC-MAG10K', name: 'Aurion MagCharge 10K', type: 'simple', device: 'powerbank', category: 'power-charging', brand: 'Aurion', price: 1999,
        color: 'white', battery: '10,000 mAh, 20 W', connectivity: ['USB-C'], warranty: '1 Year', isNew: true, weight: 0.21,
        short: 'A slim magnetic wireless power bank that snaps onto the back of your phone.',
        highlights: ['Magnetic wireless charging', '20 W USB-C fast charge', 'Built-in kickstand'] }),
];
