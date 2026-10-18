<?php

return [
    'required' => 'Поле «:attribute» обязательно для заполнения.',
    'integer' => 'Поле «:attribute» должно быть целым числом.',
    'numeric' => 'Поле «:attribute» должно быть числом.',
    'email' => 'Поле «:attribute» должно быть корректным email.',
    'in' => 'Выбрано недопустимое значение поля «:attribute».',
    'exists' => 'Выбрано недопустимое значение поля «:attribute».',
    'string' => 'Поле «:attribute» должно быть строкой.',
    'between' => ['numeric' => 'Поле «:attribute» должно быть между :min и :max.'],
    'confirmed' => 'Поле «:attribute» не совпадает с подтверждением.',
    'unique' => 'Такой :attribute уже зарегистрирован.',
    'min' => ['string' => 'Поле «:attribute» должно содержать не менее :min символов.'],
    'max' => ['string' => 'Поле «:attribute» не может быть длиннее :max символов.'],

    'attributes' => [
        'email' => 'email', 'password' => 'пароль',
        'gender' => 'Пол', 'age' => 'Возраст', 'height_cm' => 'Рост', 'weight_kg' => 'Вес',
        'target_weight_kg' => 'Желаемый вес', 'activity' => 'Активность', 'goal' => 'Цель',
        'meals_per_day' => 'Приёмов пищи', 'preferred_diet_id' => 'Диета', 'name' => 'Имя',
        'preferences' => 'Предпочтения', 'allergies' => 'Аллергии',
    ],
];
