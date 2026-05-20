<?php
/**
 * Database bootstrap — PDO + SQLite.
 * Creates the schema and seeds demo data on first run so the project is
 * fully self-contained ("just run it").
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }

    $fresh = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Use a marker table to decide whether to (re)build the schema.
    $hasSchema = (bool) $pdo->query(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='products'"
    )->fetchColumn();

    if ($fresh || !$hasSchema) {
        db_create_schema($pdo);
        db_seed($pdo);
    }

    return $pdo;
}

function db_create_schema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        phone TEXT DEFAULT '',
        is_admin INTEGER NOT NULL DEFAULT 0,
        reset_token TEXT DEFAULT NULL,
        reset_expires INTEGER DEFAULT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        blurb TEXT DEFAULT '',
        icon TEXT DEFAULT 'pizza',
        sort INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL REFERENCES categories(id),
        slug TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        description TEXT DEFAULT '',
        base_price REAL NOT NULL,
        image TEXT DEFAULT 'generic',
        is_veggie INTEGER NOT NULL DEFAULT 0,
        is_vegan INTEGER NOT NULL DEFAULT 0,
        is_gluten_free INTEGER NOT NULL DEFAULT 0,
        spice_level INTEGER NOT NULL DEFAULT 0,
        has_sizes INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1,
        sort INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS product_sizes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
        label TEXT NOT NULL,
        price_delta REAL NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS toppings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price REAL NOT NULL DEFAULT 1.00,
        kind TEXT NOT NULL DEFAULT 'meat'
    );

    CREATE TABLE IF NOT EXISTS crusts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price_delta REAL NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS deals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        price REAL NOT NULL,
        badge TEXT DEFAULT 'DEAL',
        image TEXT DEFAULT 'deal'
    );

    CREATE TABLE IF NOT EXISTS addresses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        label TEXT DEFAULT 'Home',
        line1 TEXT NOT NULL,
        line2 TEXT DEFAULT '',
        city TEXT NOT NULL,
        postcode TEXT NOT NULL,
        is_default INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS cart_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        ref TEXT NOT NULL,
        name TEXT NOT NULL,
        options_json TEXT DEFAULT '{}',
        qty INTEGER NOT NULL DEFAULT 1,
        unit_price REAL NOT NULL
    );

    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER REFERENCES users(id),
        order_type TEXT NOT NULL DEFAULT 'delivery',
        status TEXT NOT NULL DEFAULT 'received',
        customer_name TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT DEFAULT '',
        address TEXT DEFAULT '',
        postcode TEXT DEFAULT '',
        time_slot TEXT NOT NULL,
        payment_method TEXT NOT NULL DEFAULT 'cash',
        notes TEXT DEFAULT '',
        subtotal REAL NOT NULL,
        delivery_fee REAL NOT NULL DEFAULT 0,
        total REAL NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        options_json TEXT DEFAULT '{}',
        qty INTEGER NOT NULL,
        unit_price REAL NOT NULL,
        line_total REAL NOT NULL
    );

    CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id),
        name TEXT NOT NULL,
        rating INTEGER NOT NULL,
        body TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS newsletter (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );
    SQL);
}

function db_seed(PDO $pdo): void
{
    // --- Admin + demo customer --------------------------------------------
    $ins = $pdo->prepare(
        'INSERT INTO users (name,email,password_hash,phone,is_admin)
         VALUES (?,?,?,?,?)'
    );
    $ins->execute([
        'Kitchen Admin', 'admin@pizzaandco.test',
        password_hash('admin123', PASSWORD_DEFAULT), '01642 000000', 1,
    ]);
    $ins->execute([
        'Sam Tester', 'sam@example.test',
        password_hash('password', PASSWORD_DEFAULT), '07700 900123', 0,
    ]);

    // --- Categories -------------------------------------------------------
    $categories = [
        ['pizzas', 'Stone-Baked Pizzas', 'Hand-stretched dough, 24h proved.', 'pizza'],
        ['parmos', 'Parmos', 'The Teesside classic — done properly.', 'parmo'],
        ['kebabs', 'Kebabs', 'Off the spit, fresh salad, warm bread.', 'kebab'],
        ['burgers', 'Burgers', 'Smashed patties, brioche buns.', 'burger'],
        ['fusion-pasta', 'Fusion Pasta', 'Creamy, spicy, indo-italian mash-ups.', 'pasta'],
        ['loaded-chips', 'Loaded Chips', 'Chips, but make them a meal.', 'chips'],
        ['sides', 'Sides & Bites', 'Wings, garlic bread, dough balls.', 'sides'],
        ['wraps', 'Wraps', 'Rolled, grilled, packed.', 'wrap'],
        ['desserts', 'Desserts', 'Cookie dough, chocolate bombs.', 'dessert'],
        ['drinks', 'Drinks', 'Cans, bottles, shakes.', 'drink'],
        ['party-deals', 'Party Deals', 'Feed the whole crew.', 'party'],
    ];
    $cstmt = $pdo->prepare(
        'INSERT INTO categories (slug,name,blurb,icon,sort) VALUES (?,?,?,?,?)'
    );
    $catId = [];
    foreach ($categories as $i => $c) {
        $cstmt->execute([$c[0], $c[1], $c[2], $c[3], $i]);
        $catId[$c[0]] = (int) $pdo->lastInsertId();
    }

    // --- Products: [cat, slug, name, desc, price, image, veg, vegan, gf, spice, sizes] -
    $P = [
        ['pizzas','margherita','Margherita','San Marzano tomato, fior di latte, basil.',8.50,'pizza-margherita',1,0,0,0,1],
        ['pizzas','pepperoni-storm','Pepperoni Storm','Double pepperoni, chilli honey drizzle.',10.95,'pizza-pepperoni',0,0,0,2,1],
        ['pizzas','garden-vegan','Garden Vegan','Vegan mozz, roast peppers, red onion, rocket.',10.50,'pizza-veg',1,1,0,1,1],
        ['pizzas','meat-feast','Meat Feast','Pepperoni, ham, beef, sausage, smoked bacon.',12.95,'pizza-meat',0,0,0,1,1],
        ['pizzas','bbq-chicken','BBQ Chicken','Grilled chicken, red onion, smoky BBQ base.',11.95,'pizza-bbq',0,0,0,1,1],
        ['pizzas','hot-inferno','Hot Inferno','Nduja, jalapeño, fresh chilli, chilli oil.',12.50,'pizza-hot',0,0,0,3,1],
        ['parmos','classic-parmo','Classic Chicken Parmo','Flattened chicken, béchamel, cheddar, chips.',9.95,'parmo-classic',0,0,0,0,1],
        ['parmos','hot-shot-parmo','Hot Shot Parmo','Parmo + hot sauce, jalapeños, garlic mayo.',11.50,'parmo-hot',0,0,0,3,1],
        ['parmos','veggie-parmo','Halloumi Parmo','Halloumi base, béchamel, cheddar, chips.',10.50,'parmo-veg',1,0,0,0,1],
        ['kebabs','doner-kebab','Doner Kebab','Seasoned doner, salad, pitta, choice of sauce.',8.95,'kebab-doner',0,0,0,1,1],
        ['kebabs','chicken-shish','Chicken Shish','Marinated chargrilled chicken, flatbread.',9.95,'kebab-shish',0,0,0,1,1],
        ['kebabs','mixed-grill-kebab','Mixed Grill','Doner, shish, kofte, lamb chop, salad.',14.95,'kebab-mix',0,0,0,2,0],
        ['burgers','smash-classic','Classic Smash','Double smashed beef, American cheese, house sauce.',9.50,'burger-classic',0,0,0,0,0],
        ['burgers','buffalo-chicken','Buffalo Chicken','Buttermilk chicken, buffalo glaze, blue cheese.',10.50,'burger-chicken',0,0,0,2,0],
        ['burgers','beyond-stack','Beyond Stack','Plant patty, vegan cheese, smoked relish.',10.95,'burger-vegan',1,1,0,1,0],
        ['fusion-pasta','tikka-alfredo','Chicken Tikka Alfredo','Tikka chicken, creamy alfredo, penne.',10.95,'pasta-tikka',0,0,0,2,0],
        ['fusion-pasta','nduja-rigatoni','Nduja Rigatoni','Spicy nduja, tomato cream, parmesan.',11.50,'pasta-nduja',0,0,0,3,0],
        ['fusion-pasta','garden-primavera','Garden Primavera','Seasonal veg, garlic, olive oil, vegan option.',9.95,'pasta-veg',1,1,0,0,0],
        ['loaded-chips','parmo-loaded','Parmo Loaded Chips','Chips, parmo strips, béchamel, cheddar.',7.95,'chips-parmo',0,0,0,0,0],
        ['loaded-chips','dirty-fries','Dirty Fries','Beef chilli, cheese sauce, jalapeño, sour cream.',7.50,'chips-dirty',0,0,0,2,0],
        ['loaded-chips','vegan-loaded','Vegan Loaded','Vegan cheese, BBQ jackfruit, spring onion.',7.50,'chips-vegan',1,1,0,1,0],
        ['sides','garlic-bread','Garlic Bread','Stone-baked, garlic butter. Add cheese.',4.50,'side-garlic',1,0,0,0,1],
        ['sides','buffalo-wings','Buffalo Wings (8)','Crispy wings tossed in buffalo glaze.',6.50,'side-wings',0,0,0,2,0],
        ['sides','dough-balls','Dough Balls (9)','Warm dough balls, garlic dip.',4.95,'side-dough',1,0,0,0,0],
        ['wraps','doner-wrap','Doner Wrap','Doner, salad, chilli sauce, toasted wrap.',7.95,'wrap-doner',0,0,0,1,0],
        ['wraps','falafel-wrap','Falafel Wrap','Falafel, hummus, salad, vegan-friendly.',7.50,'wrap-falafel',1,1,0,1,0],
        ['desserts','cookie-dough','Cookie Dough','Warm cookie dough, vanilla ice cream.',5.50,'dessert-cookie',1,0,0,0,0],
        ['desserts','choc-bomb','Chocolate Bomb','Molten chocolate sphere, hot sauce pour.',5.95,'dessert-bomb',1,0,0,0,0],
        ['drinks','can-cola','Cola (330ml)','Chilled can.',1.20,'drink-can',1,1,1,0,0],
        ['drinks','still-water','Still Water (500ml)','Bottled still water.',1.00,'drink-water',1,1,1,0,0],
        ['drinks','oreo-shake','Oreo Milkshake','Thick shake, crushed Oreo.',3.95,'drink-shake',1,0,0,0,0],
    ];
    $pstmt = $pdo->prepare(
        'INSERT INTO products
         (category_id,slug,name,description,base_price,image,
          is_veggie,is_vegan,is_gluten_free,spice_level,has_sizes,sort)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $sizeStmt = $pdo->prepare(
        'INSERT INTO product_sizes (product_id,label,price_delta) VALUES (?,?,?)'
    );
    foreach ($P as $i => $p) {
        $pstmt->execute([
            $catId[$p[0]], $p[1], $p[2], $p[3], $p[4], $p[5],
            $p[6], $p[7], $p[8], $p[9], $p[10], $i,
        ]);
        $pid = (int) $pdo->lastInsertId();
        if ((int) $p[10] === 1) {
            if ($p[0] === 'pizzas') {
                $sizeStmt->execute([$pid, '9" Personal', 0]);
                $sizeStmt->execute([$pid, '12" Medium', 3.00]);
                $sizeStmt->execute([$pid, '15" Large', 6.00]);
                $sizeStmt->execute([$pid, '18" Sharer', 9.50]);
            } elseif ($p[0] === 'parmos') {
                $sizeStmt->execute([$pid, 'Regular', 0]);
                $sizeStmt->execute([$pid, 'Large', 3.00]);
                $sizeStmt->execute([$pid, 'Family', 6.50]);
            } else {
                $sizeStmt->execute([$pid, 'Regular', 0]);
                $sizeStmt->execute([$pid, 'Large', 2.00]);
            }
        }
    }

    // --- Pizza-builder toppings ------------------------------------------
    $tops = [
        ['Pepperoni',1.30,'meat'],['Smoked Bacon',1.30,'meat'],
        ['Grilled Chicken',1.50,'meat'],['Spicy Beef',1.40,'meat'],
        ['Nduja',1.60,'meat'],['Ham',1.30,'meat'],
        ['Mushrooms',0.90,'veg'],['Red Onion',0.80,'veg'],
        ['Peppers',0.90,'veg'],['Jalapeños',0.90,'veg'],
        ['Sweetcorn',0.80,'veg'],['Fresh Chilli',0.90,'veg'],
        ['Rocket',0.90,'veg'],['Pineapple',0.90,'veg'],
        ['Extra Mozzarella',1.20,'cheese'],['Vegan Cheese',1.40,'cheese'],
        ['Goats Cheese',1.50,'cheese'],['Blue Cheese',1.50,'cheese'],
    ];
    $tstmt = $pdo->prepare('INSERT INTO toppings (name,price,kind) VALUES (?,?,?)');
    foreach ($tops as $t) {
        $tstmt->execute($t);
    }

    $crusts = [
        ['Classic Hand-Stretched', 0],
        ['Stuffed Cheese Crust', 2.50],
        ['Garlic & Herb Crust', 1.50],
        ['Gluten-Free Base', 2.00],
        ['Vegan Base', 1.50],
    ];
    $crstmt = $pdo->prepare('INSERT INTO crusts (name,price_delta) VALUES (?,?)');
    foreach ($crusts as $c) {
        $crstmt->execute($c);
    }

    // --- Deals ------------------------------------------------------------
    $deals = [
        ['Match-Day Bundle', '2 large pizzas, garlic bread, wings & a bottle of pop.', 26.95, 'BEST VALUE', 'deal-match'],
        ['Parmo Party (x4)', '4 classic parmos with chips. Sorted for the squad.', 32.00, 'GROUP', 'deal-parmo'],
        ['Meal for One', 'Any 9" pizza, loaded chips & a can.', 13.50, 'SOLO', 'deal-solo'],
        ['Veggie Feast', '2 veggie pizzas, dough balls & 2 drinks.', 23.50, 'PLANT', 'deal-veg'],
    ];
    $dstmt = $pdo->prepare(
        'INSERT INTO deals (title,description,price,badge,image) VALUES (?,?,?,?,?)'
    );
    foreach ($deals as $d) {
        $dstmt->execute($d);
    }

    // --- A couple of seed reviews ----------------------------------------
    $rv = $pdo->prepare(
        'INSERT INTO reviews (product_id,name,rating,body) VALUES (?,?,?,?)'
    );
    $rv->execute([1, 'Jordan M.', 5, 'Best margherita in Thornaby, no debate.']);
    $rv->execute([7, 'Becca T.', 5, 'The parmo is unreal. Proper Teesside.']);
    $rv->execute([2, 'Aaron K.', 4, 'Pepperoni Storm has a real kick — love it.']);
}
