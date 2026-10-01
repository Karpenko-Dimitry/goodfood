<?php

// Content for DietSeeder. Each translatable field: ['ru' => ..., 'en' => ..., 'uk' => ...].
// List fields (pros, cons, allowed_foods, forbidden_foods) are "one item per line".

$img = fn (string $id) => "https://images.unsplash.com/photo-{$id}?w=1200&q=80&auto=format&fit=crop";

return [
    [
        'slug' => 'mediterranean',
        'icon' => '🫒', 'difficulty' => 1, 'duration_days' => null,
        'calories_min' => 1800, 'calories_max' => 2400, 'protein_pct' => 18, 'fat_pct' => 37, 'carbs_pct' => 45,
        'is_featured' => true, 'sort' => 1, 'image_url' => $img('1498837167922-ddd27525d352'),
        'name' => ['ru' => 'Средиземноморская диета', 'en' => 'Mediterranean Diet', 'uk' => 'Середземноморська дієта'],
        'excerpt' => [
            'ru' => 'Овощи, оливковое масло, рыба и цельные злаки. Признана одной из самых полезных систем питания в мире.',
            'en' => 'Vegetables, olive oil, fish and whole grains. Widely ranked as one of the healthiest eating patterns in the world.',
            'uk' => 'Овочі, оливкова олія, риба та цільні злаки. Визнана однією з найкорисніших систем харчування у світі.',
        ],
        'description' => [
            'ru' => '<p>Средиземноморская диета основана на традиционном питании Греции, Италии и Испании. Это не строгий план, а образ жизни: много растительной пищи, полезные жиры, умеренное количество рыбы и птицы, минимум красного мяса и сладкого.</p><h3>Кому подходит</h3><p>Практически всем: для снижения веса, профилактики сердечно-сосудистых заболеваний и диабета 2 типа, а также для долгосрочного здорового питания.</p>',
            'en' => '<p>The Mediterranean diet is based on the traditional eating habits of Greece, Italy and Spain. It is a lifestyle rather than a strict plan: plenty of plant foods, healthy fats, moderate fish and poultry, and little red meat or sweets.</p><h3>Who it suits</h3><p>Almost everyone: weight loss, prevention of heart disease and type 2 diabetes, and long-term healthy eating.</p>',
            'uk' => '<p>Середземноморська дієта базується на традиційному харчуванні Греції, Італії та Іспанії. Це не суворий план, а спосіб життя: багато рослинної їжі, корисні жири, помірна кількість риби та птиці, мінімум червоного м’яса й солодкого.</p><h3>Кому підходить</h3><p>Майже всім: для зниження ваги, профілактики серцево-судинних захворювань і діабету 2 типу, а також для довготривалого здорового харчування.</p>',
        ],
        'pros' => [
            'ru' => "Доказанная польза для сердца\nЛегко соблюдать долгое время\nРазнообразное и вкусное меню\nНе требует подсчёта калорий",
            'en' => "Proven heart benefits\nEasy to follow long-term\nVaried and tasty menu\nNo strict calorie counting",
            'uk' => "Доведена користь для серця\nЛегко дотримуватися тривалий час\nРізноманітне та смачне меню\nНе потребує підрахунку калорій",
        ],
        'cons' => [
            'ru' => "Рыба и оливковое масло могут быть дорогими\nСнижение веса происходит медленнее",
            'en' => "Fish and olive oil can be pricey\nWeight loss can be slower",
            'uk' => "Риба та оливкова олія можуть бути дорогими\nЗниження ваги відбувається повільніше",
        ],
        'allowed_foods' => [
            'ru' => "Овощи и фрукты\nОливковое масло\nРыба и морепродукты\nБобовые и орехи\nЦельнозерновой хлеб и крупы\nЙогурт и сыр в умеренных количествах",
            'en' => "Vegetables and fruit\nOlive oil\nFish and seafood\nLegumes and nuts\nWholegrain bread and grains\nYoghurt and cheese in moderation",
            'uk' => "Овочі та фрукти\nОливкова олія\nРиба та морепродукти\nБобові та горіхи\nЦільнозерновий хліб і крупи\nЙогурт і сир у помірній кількості",
        ],
        'forbidden_foods' => [
            'ru' => "Переработанное мясо\nСладкие напитки\nРафинированные масла\nФастфуд",
            'en' => "Processed meat\nSugary drinks\nRefined oils\nFast food",
            'uk' => "Перероблене м’ясо\nСолодкі напої\nРафіновані олії\nФастфуд",
        ],
    ],
    [
        'slug' => 'keto',
        'icon' => '🥑', 'difficulty' => 3, 'duration_days' => 30,
        'calories_min' => 1500, 'calories_max' => 2200, 'protein_pct' => 20, 'fat_pct' => 75, 'carbs_pct' => 5,
        'is_featured' => true, 'sort' => 2, 'image_url' => $img('1467003909585-2f8a72700288'),
        'name' => ['ru' => 'Кето-диета', 'en' => 'Keto Diet', 'uk' => 'Кето-дієта'],
        'excerpt' => [
            'ru' => 'Высокое содержание жиров и почти без углеводов: организм переходит в кетоз и сжигает жир как основное топливо.',
            'en' => 'High fat and very low carb: the body enters ketosis and burns fat as its main fuel.',
            'uk' => 'Високий вміст жирів і майже без вуглеводів: організм переходить у кетоз і спалює жир як основне паливо.',
        ],
        'description' => [
            'ru' => '<p>Кетогенная диета ограничивает углеводы до 20–50 г в день. Через 2–4 дня организм начинает вырабатывать кетоны и использовать жир в качестве источника энергии.</p><h3>Важно</h3><p>Первые дни возможен «кето-грипп»: слабость, головная боль. Пейте больше воды и добавляйте электролиты. Перед началом проконсультируйтесь с врачом.</p>',
            'en' => '<p>The ketogenic diet limits carbs to 20–50 g per day. After 2–4 days the body starts producing ketones and using fat for energy.</p><h3>Important</h3><p>The first days may bring the “keto flu”: fatigue and headaches. Drink more water and add electrolytes. Consult a doctor before starting.</p>',
            'uk' => '<p>Кетогенна дієта обмежує вуглеводи до 20–50 г на день. Через 2–4 дні організм починає виробляти кетони й використовувати жир як джерело енергії.</p><h3>Важливо</h3><p>У перші дні можливий «кето-грип»: слабкість, головний біль. Пийте більше води та додавайте електроліти. Перед початком проконсультуйтеся з лікарем.</p>',
        ],
        'pros' => [
            'ru' => "Быстрое снижение веса\nСтабильный уровень сахара в крови\nСнижение аппетита",
            'en' => "Fast weight loss\nStable blood sugar\nReduced appetite",
            'uk' => "Швидке зниження ваги\nСтабільний рівень цукру в крові\nЗниження апетиту",
        ],
        'cons' => [
            'ru' => "Сложно соблюдать в долгосрочной перспективе\nРиск «кето-гриппа»\nОграниченный выбор фруктов и круп",
            'en' => "Hard to sustain long-term\nRisk of “keto flu”\nVery limited fruit and grains",
            'uk' => "Складно дотримуватися довгостроково\nРизик «кето-грипу»\nОбмежений вибір фруктів і круп",
        ],
        'allowed_foods' => [
            'ru' => "Мясо, рыба, яйца\nАвокадо\nСливочное и оливковое масло\nСыр\nЛистовые овощи\nОрехи и семена",
            'en' => "Meat, fish, eggs\nAvocado\nButter and olive oil\nCheese\nLeafy greens\nNuts and seeds",
            'uk' => "М’ясо, риба, яйця\nАвокадо\nВершкове та оливкова олія\nСир\nЛистові овочі\nГоріхи та насіння",
        ],
        'forbidden_foods' => [
            'ru' => "Сахар и сладости\nХлеб, макароны, крупы\nКартофель\nБольшинство фруктов\nПиво и сладкий алкоголь",
            'en' => "Sugar and sweets\nBread, pasta, grains\nPotatoes\nMost fruit\nBeer and sweet alcohol",
            'uk' => "Цукор і солодощі\nХліб, макарони, крупи\nКартопля\nБільшість фруктів\nПиво та солодкий алкоголь",
        ],
    ],
    [
        'slug' => 'intermittent-fasting',
        'icon' => '⏱️', 'difficulty' => 2, 'duration_days' => null,
        'calories_min' => 1600, 'calories_max' => 2300, 'protein_pct' => 25, 'fat_pct' => 30, 'carbs_pct' => 45,
        'is_featured' => true, 'sort' => 3, 'image_url' => $img('1543339308-43e59d6b73a6'),
        'name' => ['ru' => 'Интервальное голодание 16/8', 'en' => 'Intermittent Fasting 16/8', 'uk' => 'Інтервальне голодування 16/8'],
        'excerpt' => [
            'ru' => '16 часов без еды и 8-часовое «окно» питания. Важно не что есть, а когда.',
            'en' => '16 hours of fasting and an 8-hour eating window. It is about when you eat, not only what.',
            'uk' => '16 годин без їжі та 8-годинне «вікно» харчування. Важливо не що їсти, а коли.',
        ],
        'description' => [
            'ru' => '<p>Схема 16/8 — самый популярный вариант интервального голодания. Например, вы едите с 12:00 до 20:00, а остальное время пьёте воду, чай или чёрный кофе.</p><p>Внутри окна питания придерживайтесь сбалансированного рациона с достаточным количеством белка и овощей.</p>',
            'en' => '<p>The 16/8 schedule is the most popular form of intermittent fasting. For example, you eat between 12:00 and 20:00 and drink only water, tea or black coffee the rest of the time.</p><p>Within the eating window stick to balanced meals with enough protein and vegetables.</p>',
            'uk' => '<p>Схема 16/8 — найпопулярніший варіант інтервального голодування. Наприклад, ви їсте з 12:00 до 20:00, а решту часу п’єте воду, чай або чорну каву.</p><p>У межах вікна харчування дотримуйтеся збалансованого раціону з достатньою кількістю білка й овочів.</p>',
        ],
        'pros' => [
            'ru' => "Простые правила\nНе нужно отказываться от любимых продуктов\nУлучшает чувствительность к инсулину",
            'en' => "Simple rules\nNo need to give up favourite foods\nImproves insulin sensitivity",
            'uk' => "Прості правила\nНе потрібно відмовлятися від улюблених продуктів\nПокращує чутливість до інсуліну",
        ],
        'cons' => [
            'ru' => "Голод в первые дни\nНе подходит при диабете и беременности без контроля врача",
            'en' => "Hunger in the first days\nNot for diabetes or pregnancy without medical supervision",
            'uk' => "Голод у перші дні\nНе підходить при діабеті та вагітності без контролю лікаря",
        ],
        'allowed_foods' => [
            'ru' => "Любые цельные продукты в окне питания\nВода, чай, чёрный кофе во время голодания",
            'en' => "Any whole foods during the eating window\nWater, tea, black coffee while fasting",
            'uk' => "Будь-які цільні продукти у вікні харчування\nВода, чай, чорна кава під час голодування",
        ],
        'forbidden_foods' => [
            'ru' => "Калорийные напитки во время голодания\nПереедание в окне питания",
            'en' => "Caloric drinks while fasting\nOvereating in the eating window",
            'uk' => "Калорійні напої під час голодування\nПереїдання у вікні харчування",
        ],
    ],
    [
        'slug' => 'vegan',
        'icon' => '🌱', 'difficulty' => 2, 'duration_days' => null,
        'calories_min' => 1700, 'calories_max' => 2400, 'protein_pct' => 15, 'fat_pct' => 25, 'carbs_pct' => 60,
        'is_featured' => true, 'sort' => 4, 'image_url' => $img('1512621776951-a57141f2eefd'),
        'name' => ['ru' => 'Веганская диета', 'en' => 'Vegan Diet', 'uk' => 'Веганська дієта'],
        'excerpt' => [
            'ru' => 'Только растительная пища: овощи, бобовые, злаки, орехи. Богата клетчаткой и антиоксидантами.',
            'en' => 'Plant foods only: vegetables, legumes, grains and nuts. Rich in fibre and antioxidants.',
            'uk' => 'Лише рослинна їжа: овочі, бобові, злаки, горіхи. Багата на клітковину та антиоксиданти.',
        ],
        'description' => [
            'ru' => '<p>Веганство исключает все продукты животного происхождения. При грамотном планировании рацион обеспечивает все необходимые нутриенты.</p><h3>Обратите внимание</h3><p>Обязательно принимайте витамин B12 и следите за уровнем железа, омега-3 и витамина D.</p>',
            'en' => '<p>A vegan diet excludes all animal products. When well planned, it provides every nutrient you need.</p><h3>Note</h3><p>Supplement vitamin B12 and keep an eye on iron, omega-3 and vitamin D levels.</p>',
            'uk' => '<p>Веганство виключає всі продукти тваринного походження. За грамотного планування раціон забезпечує всі необхідні нутрієнти.</p><h3>Зверніть увагу</h3><p>Обов’язково приймайте вітамін B12 і стежте за рівнем заліза, омега-3 та вітаміну D.</p>',
        ],
        'pros' => [
            'ru' => "Много клетчатки\nНизкий риск сердечных заболеваний\nЭкологичность",
            'en' => "High in fibre\nLower risk of heart disease\nEnvironmentally friendly",
            'uk' => "Багато клітковини\nНизький ризик серцевих захворювань\nЕкологічність",
        ],
        'cons' => [
            'ru' => "Нужно следить за B12, железом и белком\nСложнее питаться вне дома",
            'en' => "Need to watch B12, iron and protein\nHarder to eat out",
            'uk' => "Потрібно стежити за B12, залізом і білком\nСкладніше харчуватися поза домом",
        ],
        'allowed_foods' => [
            'ru' => "Овощи и фрукты\nБобовые и тофу\nЦельные злаки\nОрехи и семена\nРастительное молоко",
            'en' => "Vegetables and fruit\nLegumes and tofu\nWhole grains\nNuts and seeds\nPlant milk",
            'uk' => "Овочі та фрукти\nБобові та тофу\nЦільні злаки\nГоріхи та насіння\nРослинне молоко",
        ],
        'forbidden_foods' => [
            'ru' => "Мясо и рыба\nЯйца\nМолочные продукты\nМёд",
            'en' => "Meat and fish\nEggs\nDairy\nHoney",
            'uk' => "М’ясо та риба\nЯйця\nМолочні продукти\nМед",
        ],
    ],
    [
        'slug' => 'paleo',
        'icon' => '🍖', 'difficulty' => 2, 'duration_days' => null,
        'calories_min' => 1800, 'calories_max' => 2500, 'protein_pct' => 30, 'fat_pct' => 40, 'carbs_pct' => 30,
        'is_featured' => true, 'sort' => 5, 'image_url' => $img('1432139555190-58524dae6a55'),
        'name' => ['ru' => 'Палеодиета', 'en' => 'Paleo Diet', 'uk' => 'Палеодієта'],
        'excerpt' => [
            'ru' => 'Питание «как у древних людей»: мясо, рыба, овощи, фрукты и орехи — без злаков, молочного и сахара.',
            'en' => 'Eat like our ancestors: meat, fish, vegetables, fruit and nuts — no grains, dairy or sugar.',
            'uk' => 'Харчування «як у давніх людей»: м’ясо, риба, овочі, фрукти та горіхи — без злаків, молочного й цукру.',
        ],
        'description' => [
            'ru' => '<p>Палеодиета исключает продукты, появившиеся с развитием сельского хозяйства: злаки, бобовые, молочные продукты, а также всё переработанное.</p><p>Рацион богат белком и овощами, что помогает контролировать аппетит.</p>',
            'en' => '<p>The paleo diet removes foods that appeared with farming: grains, legumes, dairy, and anything processed.</p><p>The menu is rich in protein and vegetables, which helps control appetite.</p>',
            'uk' => '<p>Палеодієта виключає продукти, що з’явилися з розвитком сільського господарства: злаки, бобові, молочні продукти, а також усе перероблене.</p><p>Раціон багатий на білок і овочі, що допомагає контролювати апетит.</p>',
        ],
        'pros' => [
            'ru' => "Отказ от переработанных продуктов\nВысокое содержание белка\nХорошая насыщаемость",
            'en' => "No processed foods\nHigh in protein\nVery satiating",
            'uk' => "Відмова від перероблених продуктів\nВисокий вміст білка\nДобра насичуваність",
        ],
        'cons' => [
            'ru' => "Исключает полезные бобовые и злаки\nМожет быть дорогой",
            'en' => "Excludes healthy legumes and grains\nCan be expensive",
            'uk' => "Виключає корисні бобові та злаки\nМоже бути дорогою",
        ],
        'allowed_foods' => [
            'ru' => "Мясо и птица\nРыба и морепродукты\nЯйца\nОвощи и фрукты\nОрехи и семена",
            'en' => "Meat and poultry\nFish and seafood\nEggs\nVegetables and fruit\nNuts and seeds",
            'uk' => "М’ясо та птиця\nРиба та морепродукти\nЯйця\nОвочі та фрукти\nГоріхи та насіння",
        ],
        'forbidden_foods' => [
            'ru' => "Злаки и хлеб\nБобовые\nМолочные продукты\nСахар\nПереработанные продукты",
            'en' => "Grains and bread\nLegumes\nDairy\nSugar\nProcessed foods",
            'uk' => "Злаки та хліб\nБобові\nМолочні продукти\nЦукор\nПерероблені продукти",
        ],
    ],
    [
        'slug' => 'dash',
        'icon' => '❤️', 'difficulty' => 1, 'duration_days' => null,
        'calories_min' => 1600, 'calories_max' => 2200, 'protein_pct' => 18, 'fat_pct' => 27, 'carbs_pct' => 55,
        'is_featured' => true, 'sort' => 6, 'image_url' => $img('1540189549336-e6e99c3679fe'),
        'name' => ['ru' => 'Диета DASH', 'en' => 'DASH Diet', 'uk' => 'Дієта DASH'],
        'excerpt' => [
            'ru' => 'Разработана для снижения давления: меньше соли, больше овощей, фруктов и нежирных молочных продуктов.',
            'en' => 'Designed to lower blood pressure: less salt, more vegetables, fruit and low-fat dairy.',
            'uk' => 'Розроблена для зниження тиску: менше солі, більше овочів, фруктів і нежирних молочних продуктів.',
        ],
        'description' => [
            'ru' => '<p>DASH (Dietary Approaches to Stop Hypertension) рекомендована кардиологами. Основной принцип — ограничение натрия до 1500–2300 мг в день и увеличение калия, кальция и магния.</p>',
            'en' => '<p>DASH (Dietary Approaches to Stop Hypertension) is recommended by cardiologists. The core principle is limiting sodium to 1,500–2,300 mg a day and increasing potassium, calcium and magnesium.</p>',
            'uk' => '<p>DASH (Dietary Approaches to Stop Hypertension) рекомендована кардіологами. Основний принцип — обмеження натрію до 1500–2300 мг на день і збільшення калію, кальцію та магнію.</p>',
        ],
        'pros' => [
            'ru' => "Снижает артериальное давление\nСбалансирована и безопасна\nПодходит всей семье",
            'en' => "Lowers blood pressure\nBalanced and safe\nSuits the whole family",
            'uk' => "Знижує артеріальний тиск\nЗбалансована та безпечна\nПідходить усій родині",
        ],
        'cons' => [
            'ru' => "Требует контроля соли\nМедленное снижение веса",
            'en' => "Requires watching salt\nSlow weight loss",
            'uk' => "Потребує контролю солі\nПовільне зниження ваги",
        ],
        'allowed_foods' => [
            'ru' => "Овощи и фрукты\nЦельные злаки\nНежирные молочные продукты\nПтица и рыба\nОрехи и бобовые",
            'en' => "Vegetables and fruit\nWhole grains\nLow-fat dairy\nPoultry and fish\nNuts and legumes",
            'uk' => "Овочі та фрукти\nЦільні злаки\nНежирні молочні продукти\nПтиця та риба\nГоріхи та бобові",
        ],
        'forbidden_foods' => [
            'ru' => "Солёные закуски\nКолбасы и консервы\nСладкие напитки\nЖирное красное мясо",
            'en' => "Salty snacks\nSausages and canned food\nSugary drinks\nFatty red meat",
            'uk' => "Солоні закуски\nКовбаси та консерви\nСолодкі напої\nЖирне червоне м’ясо",
        ],
    ],
    [
        'slug' => 'low-carb',
        'icon' => '🥩', 'difficulty' => 2, 'duration_days' => null,
        'calories_min' => 1500, 'calories_max' => 2200, 'protein_pct' => 30, 'fat_pct' => 45, 'carbs_pct' => 25,
        'is_featured' => false, 'sort' => 7, 'image_url' => $img('1546069901-ba9599a7e63c'),
        'name' => ['ru' => 'Низкоуглеводная диета', 'en' => 'Low-Carb Diet', 'uk' => 'Низьковуглеводна дієта'],
        'excerpt' => [
            'ru' => 'Мягкая альтернатива кето: 50–130 г углеводов в день, акцент на белок и овощи.',
            'en' => 'A gentler alternative to keto: 50–130 g of carbs a day with a focus on protein and vegetables.',
            'uk' => 'М’яка альтернатива кето: 50–130 г вуглеводів на день, акцент на білок та овочі.',
        ],
        'description' => [
            'ru' => '<p>Низкоуглеводное питание сокращает сахар и крахмалистые продукты, но не так радикально, как кето. Это помогает снизить вес и уровень инсулина без жёстких ограничений.</p>',
            'en' => '<p>Low-carb eating cuts sugar and starchy foods but less drastically than keto. It helps reduce weight and insulin levels without harsh restrictions.</p>',
            'uk' => '<p>Низьковуглеводне харчування скорочує цукор і крохмалисті продукти, але не так радикально, як кето. Це допомагає знизити вагу та рівень інсуліну без жорстких обмежень.</p>',
        ],
        'pros' => ['ru' => "Эффективное снижение веса\nМеньше тяги к сладкому", 'en' => "Effective weight loss\nFewer sugar cravings", 'uk' => "Ефективне зниження ваги\nМенше тяги до солодкого"],
        'cons' => ['ru' => "Нужно планировать меню\nМеньше клетчатки при неправильном подходе", 'en' => "Requires meal planning\nLess fibre if done poorly", 'uk' => "Потрібно планувати меню\nМенше клітковини за неправильного підходу"],
        'allowed_foods' => ['ru' => "Мясо, рыба, яйца\nНекрахмалистые овощи\nЯгоды\nОрехи\nМолочные продукты", 'en' => "Meat, fish, eggs\nNon-starchy vegetables\nBerries\nNuts\nDairy", 'uk' => "М’ясо, риба, яйця\nНекрохмалисті овочі\nЯгоди\nГоріхи\nМолочні продукти"],
        'forbidden_foods' => ['ru' => "Сахар\nБелый хлеб и выпечка\nСладкие напитки", 'en' => "Sugar\nWhite bread and pastries\nSugary drinks", 'uk' => "Цукор\nБілий хліб і випічка\nСолодкі напої"],
    ],
    [
        'slug' => 'high-protein',
        'icon' => '💪', 'difficulty' => 2, 'duration_days' => null,
        'calories_min' => 2000, 'calories_max' => 2800, 'protein_pct' => 35, 'fat_pct' => 25, 'carbs_pct' => 40,
        'is_featured' => false, 'sort' => 8, 'image_url' => $img('1546793665-c74683f339c1'),
        'name' => ['ru' => 'Высокобелковая диета', 'en' => 'High-Protein Diet', 'uk' => 'Високобілкова дієта'],
        'excerpt' => [
            'ru' => 'Для спортсменов и тех, кто хочет сохранить мышцы при похудении: 1,6–2,2 г белка на кг веса.',
            'en' => 'For athletes and anyone who wants to keep muscle while losing fat: 1.6–2.2 g of protein per kg.',
            'uk' => 'Для спортсменів і тих, хто хоче зберегти м’язи під час схуднення: 1,6–2,2 г білка на кг ваги.',
        ],
        'description' => [
            'ru' => '<p>Высокобелковый рацион помогает наращивать и сохранять мышечную массу, ускоряет восстановление после тренировок и дольше сохраняет чувство сытости.</p>',
            'en' => '<p>A high-protein diet helps build and preserve muscle, speeds up recovery after workouts and keeps you full for longer.</p>',
            'uk' => '<p>Високобілковий раціон допомагає нарощувати та зберігати м’язову масу, прискорює відновлення після тренувань і довше зберігає відчуття ситості.</p>',
        ],
        'pros' => ['ru' => "Сохраняет мышцы\nДолгое насыщение\nПодходит для спорта", 'en' => "Preserves muscle\nLong-lasting satiety\nGreat for sport", 'uk' => "Зберігає м’язи\nТривале насичення\nПідходить для спорту"],
        'cons' => ['ru' => "Не рекомендуется при болезнях почек\nТребует контроля жиров", 'en' => "Not advised with kidney disease\nNeeds fat control", 'uk' => "Не рекомендується при хворобах нирок\nПотребує контролю жирів"],
        'allowed_foods' => ['ru' => "Курица, индейка\nРыба\nЯйца\nТворог и греческий йогурт\nБобовые", 'en' => "Chicken, turkey\nFish\nEggs\nCottage cheese and Greek yoghurt\nLegumes", 'uk' => "Курка, індичка\nРиба\nЯйця\nСир кисломолочний і грецький йогурт\nБобові"],
        'forbidden_foods' => ['ru' => "Фастфуд\nСладости\nЖирные соусы", 'en' => "Fast food\nSweets\nFatty sauces", 'uk' => "Фастфуд\nСолодощі\nЖирні соуси"],
    ],
    [
        'slug' => 'flexitarian',
        'icon' => '🥗', 'difficulty' => 1, 'duration_days' => null,
        'calories_min' => 1700, 'calories_max' => 2300, 'protein_pct' => 18, 'fat_pct' => 30, 'carbs_pct' => 52,
        'is_featured' => false, 'sort' => 9, 'image_url' => $img('1606787366850-de6330128bfc'),
        'name' => ['ru' => 'Флекситарианство', 'en' => 'Flexitarian Diet', 'uk' => 'Флекситаріанство'],
        'excerpt' => [
            'ru' => 'В основном растительное питание с периодическим включением мяса и рыбы. Гибко и без запретов.',
            'en' => 'Mostly plant-based eating with occasional meat and fish. Flexible and without strict bans.',
            'uk' => 'Переважно рослинне харчування з періодичним включенням м’яса та риби. Гнучко й без заборон.',
        ],
        'description' => [
            'ru' => '<p>Флекситарианство — компромисс между вегетарианством и обычным питанием. Мясо — 1–3 раза в неделю, основа рациона — овощи, бобовые и злаки.</p>',
            'en' => '<p>Flexitarianism is a compromise between vegetarian and regular eating. Meat 1–3 times a week, with vegetables, legumes and grains as the base.</p>',
            'uk' => '<p>Флекситаріанство — компроміс між вегетаріанством і звичайним харчуванням. М’ясо — 1–3 рази на тиждень, основа раціону — овочі, бобові та злаки.</p>',
        ],
        'pros' => ['ru' => "Легко начать\nПольза растительной диеты без жёстких ограничений", 'en' => "Easy to start\nPlant-based benefits without strict rules", 'uk' => "Легко почати\nКористь рослинної дієти без жорстких обмежень"],
        'cons' => ['ru' => "Нет чётких правил — легко сорваться", 'en' => "No clear rules — easy to slip", 'uk' => "Немає чітких правил — легко зірватися"],
        'allowed_foods' => ['ru' => "Овощи, фрукты\nБобовые\nЦельные злаки\nЯйца и молочные\nМясо и рыба изредка", 'en' => "Vegetables, fruit\nLegumes\nWhole grains\nEggs and dairy\nMeat and fish occasionally", 'uk' => "Овочі, фрукти\nБобові\nЦільні злаки\nЯйця та молочні\nМ’ясо й риба зрідка"],
        'forbidden_foods' => ['ru' => "Переработанное мясо\nСахар в избытке", 'en' => "Processed meat\nExcess sugar", 'uk' => "Перероблене м’ясо\nНадлишок цукру"],
    ],
    [
        'slug' => 'nordic',
        'icon' => '🐟', 'difficulty' => 1, 'duration_days' => null,
        'calories_min' => 1800, 'calories_max' => 2400, 'protein_pct' => 22, 'fat_pct' => 33, 'carbs_pct' => 45,
        'is_featured' => false, 'sort' => 10, 'image_url' => $img('1519708227418-c8fd9a32b7a2'),
        'name' => ['ru' => 'Скандинавская диета', 'en' => 'Nordic Diet', 'uk' => 'Скандинавська дієта'],
        'excerpt' => [
            'ru' => 'Жирная северная рыба, рожь, ягоды и рапсовое масло. Северный ответ средиземноморской диете.',
            'en' => 'Oily northern fish, rye, berries and rapeseed oil. The Nordic answer to the Mediterranean diet.',
            'uk' => 'Жирна північна риба, жито, ягоди та ріпакова олія. Північна відповідь середземноморській дієті.',
        ],
        'description' => [
            'ru' => '<p>Скандинавская диета делает акцент на сезонных и местных продуктах: лосось, сельдь, ржаной хлеб, корнеплоды, капуста и лесные ягоды.</p>',
            'en' => '<p>The Nordic diet focuses on seasonal and local foods: salmon, herring, rye bread, root vegetables, cabbage and wild berries.</p>',
            'uk' => '<p>Скандинавська дієта робить акцент на сезонних і місцевих продуктах: лосось, оселедець, житній хліб, коренеплоди, капуста та лісові ягоди.</p>',
        ],
        'pros' => ['ru' => "Много омега-3\nДоступные продукты\nПольза для сердца", 'en' => "Rich in omega-3\nAffordable foods\nHeart-friendly", 'uk' => "Багато омега-3\nДоступні продукти\nКористь для серця"],
        'cons' => ['ru' => "Меньше исследований, чем у средиземноморской", 'en' => "Less researched than Mediterranean", 'uk' => "Менше досліджень, ніж у середземноморської"],
        'allowed_foods' => ['ru' => "Лосось, сельдь, скумбрия\nРжаной хлеб, овёс\nЯгоды\nКорнеплоды и капуста\nРапсовое масло", 'en' => "Salmon, herring, mackerel\nRye bread, oats\nBerries\nRoot vegetables and cabbage\nRapeseed oil", 'uk' => "Лосось, оселедець, скумбрія\nЖитній хліб, овес\nЯгоди\nКоренеплоди та капуста\nРіпакова олія"],
        'forbidden_foods' => ['ru' => "Переработанные продукты\nСахар\nФастфуд", 'en' => "Processed foods\nSugar\nFast food", 'uk' => "Перероблені продукти\nЦукор\nФастфуд"],
    ],
];
