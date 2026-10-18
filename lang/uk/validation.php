<?php

return [
    'required' => 'Поле «:attribute» є обов’язковим.',
    'integer' => 'Поле «:attribute» має бути цілим числом.',
    'numeric' => 'Поле «:attribute» має бути числом.',
    'email' => 'Поле «:attribute» має бути коректним email.',
    'in' => 'Вибрано неприпустиме значення поля «:attribute».',
    'exists' => 'Вибрано неприпустиме значення поля «:attribute».',
    'string' => 'Поле «:attribute» має бути рядком.',
    'between' => ['numeric' => 'Поле «:attribute» має бути між :min і :max.'],
    'confirmed' => 'Поле «:attribute» не збігається з підтвердженням.',
    'unique' => 'Такий :attribute вже зареєстрований.',
    'min' => ['string' => 'Поле «:attribute» має містити щонайменше :min символів.'],
    'max' => ['string' => 'Поле «:attribute» не може бути довшим за :max символів.'],

    'attributes' => [
        'email' => 'email', 'password' => 'пароль',
        'gender' => 'Стать', 'age' => 'Вік', 'height_cm' => 'Зріст', 'weight_kg' => 'Вага',
        'target_weight_kg' => 'Бажана вага', 'activity' => 'Активність', 'goal' => 'Мета',
        'meals_per_day' => 'Прийомів їжі', 'preferred_diet_id' => 'Дієта', 'name' => 'Ім’я',
        'preferences' => 'Вподобання', 'allergies' => 'Алергії',
    ],
];
