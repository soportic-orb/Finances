<?php

declare(strict_types=1);

/**
 * Joc de categories per defecte en català.
 * L'instal·lador les insereix per a la llar creada (pares i fills).
 *
 * kind: ingres | despesa | traspas
 */

return [
    // Ingressos
    ['name' => 'Ingressos', 'kind' => 'ingres', 'icon' => '💰', 'color' => '#22c55e', 'children' => [
        ['name' => 'Nòmina', 'icon' => '🧾'],
        ['name' => 'Autònom / Factures', 'icon' => '🧮'],
        ['name' => 'Rendiments', 'icon' => '📈'],
        ['name' => 'Altres ingressos', 'icon' => '➕'],
    ]],

    // Despeses
    ['name' => 'Habitatge', 'kind' => 'despesa', 'icon' => '🏠', 'color' => '#ef4444', 'children' => [
        ['name' => 'Lloguer / Hipoteca', 'icon' => '🔑'],
        ['name' => 'Comunitat', 'icon' => '🏢'],
        ['name' => 'Manteniment llar', 'icon' => '🔧'],
        ['name' => 'Assegurança llar', 'icon' => '🛡️'],
    ]],
    ['name' => 'Subministraments', 'kind' => 'despesa', 'icon' => '💡', 'color' => '#f59e0b', 'children' => [
        ['name' => 'Electricitat', 'icon' => '⚡'],
        ['name' => 'Aigua', 'icon' => '🚰'],
        ['name' => 'Gas', 'icon' => '🔥'],
        ['name' => 'Internet i telefonia', 'icon' => '📶'],
    ]],
    ['name' => 'Alimentació', 'kind' => 'despesa', 'icon' => '🛒', 'color' => '#10b981', 'children' => [
        ['name' => 'Supermercat', 'icon' => '🥦'],
        ['name' => 'Mercat / Fruiteria', 'icon' => '🍎'],
    ]],
    ['name' => 'Restaurants', 'kind' => 'despesa', 'icon' => '🍽️', 'color' => '#f97316', 'children' => [
        ['name' => 'Restaurants', 'icon' => '🍝'],
        ['name' => 'Cafè / Bar', 'icon' => '☕'],
        ['name' => 'Menjar a domicili', 'icon' => '🛵'],
    ]],
    ['name' => 'Transport', 'kind' => 'despesa', 'icon' => '🚗', 'color' => '#3b82f6', 'children' => [
        ['name' => 'Combustible', 'icon' => '⛽'],
        ['name' => 'Transport públic', 'icon' => '🚇'],
        ['name' => 'Pàrquing i peatges', 'icon' => '🅿️'],
        ['name' => 'Manteniment vehicle', 'icon' => '🔩'],
    ]],
    ['name' => 'Salut', 'kind' => 'despesa', 'icon' => '🩺', 'color' => '#06b6d4', 'children' => [
        ['name' => 'Farmàcia', 'icon' => '💊'],
        ['name' => 'Metge / Dentista', 'icon' => '🦷'],
        ['name' => 'Assegurança mèdica', 'icon' => '🏥'],
    ]],
    ['name' => 'Oci', 'kind' => 'despesa', 'icon' => '🎉', 'color' => '#a855f7', 'children' => [
        ['name' => 'Cultura i espectacles', 'icon' => '🎭'],
        ['name' => 'Esport', 'icon' => '🏃'],
        ['name' => 'Viatges', 'icon' => '✈️'],
        ['name' => 'Hobbies', 'icon' => '🎨'],
    ]],
    ['name' => 'Subscripcions', 'kind' => 'despesa', 'icon' => '🔁', 'color' => '#ec4899', 'children' => [
        ['name' => 'Streaming', 'icon' => '📺'],
        ['name' => 'Programari / Apps', 'icon' => '💻'],
        ['name' => 'Altres subscripcions', 'icon' => '📦'],
    ]],
    ['name' => 'Compres', 'kind' => 'despesa', 'icon' => '🛍️', 'color' => '#8b5cf6', 'children' => [
        ['name' => 'Roba', 'icon' => '👕'],
        ['name' => 'Llar i parament', 'icon' => '🛋️'],
        ['name' => 'Tecnologia', 'icon' => '📱'],
    ]],
    ['name' => 'Educació', 'kind' => 'despesa', 'icon' => '📚', 'color' => '#0ea5e9', 'children' => [
        ['name' => 'Escola / Universitat', 'icon' => '🎓'],
        ['name' => 'Llibres i material', 'icon' => '✏️'],
        ['name' => 'Formació', 'icon' => '🧠'],
    ]],
    ['name' => 'Impostos i taxes', 'kind' => 'despesa', 'icon' => '🏛️', 'color' => '#64748b', 'children' => []],
    ['name' => 'Comissions bancàries', 'kind' => 'despesa', 'icon' => '🏦', 'color' => '#94a3b8', 'children' => []],
    ['name' => 'Altres despeses', 'kind' => 'despesa', 'icon' => '❓', 'color' => '#6b7280', 'children' => []],

    // Estalvi / Inversió i traspassos
    ['name' => 'Estalvi i inversió', 'kind' => 'traspas', 'icon' => '🐷', 'color' => '#14b8a6', 'children' => [
        ['name' => 'Estalvi', 'icon' => '💶'],
        ['name' => 'Inversió', 'icon' => '📊'],
    ]],
    ['name' => 'Traspàs entre comptes', 'kind' => 'traspas', 'icon' => '🔄', 'color' => '#475569', 'children' => []],
];
