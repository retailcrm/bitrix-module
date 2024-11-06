const setupFieldsListElement = $('input[name="SETUP_FIELDS_LIST"]');
let customProperties = [
    {code: 'newProperty2', title: 'Новое свойство'},
    {code: 'newProperty3', title: 'Новое свойство 2'},
];

document.getElementById('submit-form').addEventListener('submit', function (formEvent) {
    formEvent.preventDefault();
    // setCustomProperties();
    // addNewParametersToSetupFieldsList();

    BX.ajax.runAction('intaro:retailcrm.api.customexportprops.save', {
        json: {properties: customProperties},
    }).then(function(response) {
        console.log(response);
        // this.submit();
    });
});

function setCustomProperties()
{
    let customPropertiesRaws = $('tr[data-type="custom-property-raw"]');
    let customPropertyTitle = '';
    let customPropertyCode = '';
    let productPropertyMatch = '';
    let offerPropertyMatch = '';

    customPropertiesRaws.each(function (rawElem) {
        let raw = $(rawElem);
        console.log(raw.find('input[name=custom-property-title]').val());
        customPropertyTitle = raw.find('input[name=custom-property-title]').value;
        customPropertyCode = getUniquePropertyCode(customPropertyTitle);
        productPropertyMatch = raw.find('select[name=custom-product-property-select]').value;
        offerPropertyMatch = raw.find('select[name=custom-offer-property-select]').value;

        customProperties.push({
            'title': customPropertyTitle,
            'code': customPropertyCode,
            'productProperty': productPropertyMatch,
            'offerProperty': offerPropertyMatch
        });
    });
}

function getUniquePropertyCode(customPropertyTitle)
{
    const setupFieldsListValues = setupFieldsListElement.val().split(',');
    let uniqueValue = customPropertyTitle;
    console.log(uniqueValue);
    let counter = 0;

    while (setupFieldsListValues.includes(uniqueValue)) {
        uniqueValue = `${customPropertyTitle}${++counter}`;
    }
    console.log(uniqueValue);

    return uniqueValue;
}

function addNewParametersToSetupFieldsList()
{
    let newParams = '';
    let parametersToFill = [
        'iblockPropertySku_',
        'iblockPropertyUnitSku_',
        'iblockPropertyProduct_',
        'iblockPropertyUnitProduct_',
        'highloadblockb_hlsys_marking_code_group_',
        'highloadblock_productb_hlsys_marking_code_group_',
        'highloadblockeshop_color_reference_',
        'highloadblock_producteshop_color_reference_',
        'highloadblockeshop_brand_reference_',
        'highloadblock_producteshop_brand_reference_'
    ];

    customProperties.forEach(function (property) {
        parametersToFill.forEach(function (param) {
            newParams += param + property.code;
        });
    });

    setupFieldsListElement.value = setupFieldsListElement.value + ',' + newParams;
}

function createNewCustomPropertyTableRaw()
{
    let elementsToCopy = document.querySelectorAll('[data-type="custom-property-raw"]');

    if (elementsToCopy.length > 0) {
        const lastElement = elementsToCopy[elementsToCopy.length - 1];
        const copyElement = lastElement.cloneNode(true);
        lastElement.after(copyElement);
    }
}