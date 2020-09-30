function calculateBonuses(leading) {
    console.log(leading);
}

jQuery('#bonus-input').on('click', _.debounce(calculateBonuses, 300, {
    'leading': 'test',
}));