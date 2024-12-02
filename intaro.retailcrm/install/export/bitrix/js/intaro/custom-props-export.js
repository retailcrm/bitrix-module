const setupFieldsListElement = $('input[name="SETUP_FIELDS_LIST"]');
let customProps = {};
let customPropsToDelete = {};
const setupFieldsParamsToFill = [
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

$('.add-custom-row').click(function () {
    createCustomPropsRaw($(this));
});

$(document).on('click', '#delete-new-custom-row', function () {
    deleteCustomPropRow($(this));
});

$(document).on('click', '#delete-custom-row', function () {
    let buttonElem = $(this);
    addCustomPropToDelete(buttonElem);
    deleteCustomPropRow(buttonElem);
});

$(document).on('blur', 'input[name="custom-property-title"]', function () {
    let inputElem = $(this);
    let newPropertyTitle = inputElem.val();

    if (!newPropertyTitle) {
        return;
    }

    let newPropertyCode = getUniquePropertyCode(newPropertyTitle);
    addCustomPropCodeToSelectAttributes(newPropertyCode, inputElem);
});

$('#submit-form').submit(function (formEvent) {
    formEvent.preventDefault();
    let savePromise = null;
    let deletePromise = null;
    let formElem = formEvent.currentTarget;
    let profileId = $($('input[name="PROFILE_ID"]')).val();

    setCustomProperties();

    if (Object.keys(customProps).length > 0) {
        savePromise = BX.ajax.runAction('intaro:retailcrm.api.customexportprops.save', {
            json: {
                properties: customProps,
                profileId: profileId
            },
        }).then(addParamsToSetupFieldsList());
    }

    if (Object.keys(customPropsToDelete).length > 0) {
        deletePromise = BX.ajax.runAction('intaro:retailcrm.api.customexportprops.delete', {
            json: {
                properties: customPropsToDelete,
                profileId: profileId
            },
        }).then(deleteParamsFromSetupFieldsList());
    }

    const promises = [savePromise, deletePromise].filter(Boolean);

    if (promises.length > 0) {
        Promise.all(promises)
            .finally(() => {
                formElem.submit();
            });
    } else {
        formElem.submit();
    }
});

function deleteCustomPropRow(deleteButton)
{
    deleteButton.closest('tr').remove();
}

function addCustomPropToDelete(deleteButton)
{
    let deletedPropTitle = deleteButton.closest('td').siblings().filter('.custom-property-title').text().trim();
    let deletedPropCode = deleteButton.siblings().filter('select').first().data('type');
    let customPropCatalogId = deleteButton.closest('.iblockExportTable').data('type');

    let values = {
        'code': deletedPropCode,
        'title': deletedPropTitle,
    };

    if (customPropsToDelete.hasOwnProperty(customPropCatalogId)) {
        customPropsToDelete[customPropCatalogId].push(values);
    } else {
        customPropsToDelete[customPropCatalogId] = [values];
    }
}

function addCustomPropCodeToSelectAttributes(customPropCode, customPropTitleElem)
{
    let selectElements = customPropTitleElem.closest('.custom-property-row').find('td select');
    let catalogId = customPropTitleElem.closest('.iblockExportTable').data('type');

    selectElements.each(function (index, element) {
        let selectElem = $(element);
        let newSelectIdValue = selectElem.attr('id').match(/^[^_]*_/)[0] + customPropCode + catalogId;
        let newSelectNameValue = selectElem.attr('name').match(/^[^_]*_/)[0] + customPropCode + `[${catalogId}]`;

        selectElem.attr('id', newSelectIdValue);
        selectElem.attr('name', newSelectNameValue);
        selectElem.data('type', customPropCode);
        triggerSelectChange(selectElem);
    });
}

function triggerSelectChange(selectElem)
{
    if (selectElem.val().length > 0) {
        selectElem.trigger('change', [self]);
    }
}

function setCustomProperties()
{
    let customPropertiesRows = $('.custom-property-row');

    if (customPropertiesRows.length === 0) {
        return;
    }

    let customPropertyCatalogId;
    let customPropertyTitle = '';
    let customPropertyCode = '';
    let productPropertyMatch = '';
    let offerPropertyMatch = '';

    let catalogIds = [];
    customPropertiesRows.each(function (index, propertyRow) {
        let propertyRowObj = $(propertyRow);
        customPropertyCatalogId = propertyRowObj.closest('.iblockExportTable').data('type');

        customPropertyTitle = propertyRowObj.find('input[name="custom-property-title"]').val();

        if (!customPropertyTitle) {
            return true;
        }

        customPropertyCode = getUniquePropertyCode(customPropertyTitle);
        productPropertyMatch = propertyRowObj.find('select[name=custom-product-property-select]').val();
        offerPropertyMatch = propertyRowObj.find('select[name=custom-offer-property-select]').val();

        let values = {
            'title': customPropertyTitle,
            'code': customPropertyCode,
            'productProperty': productPropertyMatch,
            'offerProperty': offerPropertyMatch
        };

        if (catalogIds.indexOf(customPropertyCatalogId) === -1) {
            customProps[customPropertyCatalogId] = [values];
        } else {
            customProps[customPropertyCatalogId].push(values);
        }
        catalogIds.push(customPropertyCatalogId);
    });
}

function getUniquePropertyCode(customPropertyTitle)
{
    let uniqueValue = transliterate(customPropertyTitle).replace(/ /g, '_');
    let counter = 0;

    const setupFieldsListValues = setupFieldsListElement.val().split(',');
    while (setupFieldsListValues.includes(uniqueValue)) {
        uniqueValue = `${customPropertyTitle}${++counter}`;
    }

    return uniqueValue;
}

function addParamsToSetupFieldsList()
{
    let newParams = '';

    if (Object.keys(customProps).length === 0) {
        return;
    }

    for (let propertiesByCatalogId of Object.values(customProps)) {
        propertiesByCatalogId.forEach(function (values) {
            setupFieldsParamsToFill.forEach(function (param) {
                newParams += ',' + param + values.code;
            });
        });
    }

    let newValue = setupFieldsListElement.val() + newParams;
    setupFieldsListElement.val(newValue);

    return true;
}

function deleteParamsFromSetupFieldsList()
{
    let setupFields = setupFieldsListElement.val();

    if (Object.keys(customPropsToDelete).length === 0) {
        return;
    }

    for (let propsByCatalogId of Object.values(customPropsToDelete)) {
        propsByCatalogId.forEach(function (propValues) {
            setupFieldsParamsToFill.forEach(function (param) {
                let paramToDelete = ',' + param + propValues.code;
                setupFields = setupFields.replace(paramToDelete, '');
            });
        });
    }

    setupFieldsListElement.val(setupFields);

    return true;
}

function createCustomPropsRaw(addRowButton)
{
    let templateRow = $($('#custom-property-template-row').html());
    let templateSelectElements = templateRow.find('select');

    let prevTableRow = $(addRowButton).prev('table').find('tbody tr:last-child');
    let lastRawSelectElements = prevTableRow.find('td select');

    lastRawSelectElements.each(function (index, element) {
        let selectElement = $(element);
        let templateSelectElement = templateSelectElements[index];
        fillTemplateSelect(selectElement, templateSelectElement);
        prevTableRow.after(templateRow);
    });
}

function fillTemplateSelect(sourceSelectElement, templateSelectElement)
{
    let selectOptions = sourceSelectElement.find('option');
    selectOptions.each(function (index, element) {
        let optionElem = $(element);
        let value = $(optionElem).val();
        let text = $(optionElem).text();

        $('<option>', { value: value, text: text }).appendTo(templateSelectElement);
    });
}

function transliterate(titleToTransliterate)
{
    const hasCyrillicChars = /[\u0400-\u04FF]/.test(titleToTransliterate);

    if (!hasCyrillicChars) {
        return titleToTransliterate;
    }

    translitedText = '';
    for (var i = 0; i < titleToTransliterate.length; i++) {
        switch (titleToTransliterate[i]) {
            case 'а': case 'А': translitedText += 'a'; break;
            case 'б': case 'Б': translitedText += 'b'; break;
            case 'в': case 'В': translitedText += 'v'; break;
            case 'г': case 'Г': translitedText += 'g'; break;
            case 'д': case 'Д': translitedText += 'd'; break;
            case 'е': case 'Е': translitedText += 'e'; break;
            case 'ё': case 'Ё': translitedText += 'yo'; break;
            case 'ж': case 'Ж': translitedText += 'zh'; break;
            case 'з': case 'З': translitedText += 'z'; break;
            case 'и': case 'И': translitedText += 'i'; break;
            case 'й': case 'Й': translitedText += 'y'; break;
            case 'к': case 'К': translitedText += 'k'; break;
            case 'л': case 'Л': translitedText += 'l'; break;
            case 'м': case 'М': translitedText += 'm'; break;
            case 'н': case 'Н': translitedText += 'n'; break;
            case 'о': case 'О': translitedText += 'o'; break;
            case 'п': case 'П': translitedText += 'p'; break;
            case 'р': case 'Р': translitedText += 'r'; break;
            case 'с': case 'С': translitedText += 's'; break;
            case 'т': case 'Т': translitedText += 't'; break;
            case 'у': case 'У': translitedText += 'u'; break;
            case 'ф': case 'Ф': translitedText += 'f'; break;
            case 'х': case 'Х': translitedText += 'h'; break;
            case 'ц': case 'Ц': translitedText += 'c'; break;
            case 'ч': case 'Ч': translitedText += 'ch'; break;
            case 'ш': case 'Ш': translitedText += 'sh'; break;
            case 'щ': case 'Щ': translitedText += 'sch'; break;
            case 'ъ': case 'Ъ': translitedText += ''; break;
            case 'ы': case 'Ы': translitedText += 'y'; break;
            case 'ь': case 'Ь': translitedText += ''; break;
            case 'э': case 'Э': translitedText += 'e'; break;
            case 'ю': case 'Ю': translitedText += 'yu'; break;
            case 'я': case 'Я': translitedText += 'ya'; break;
            default: translitedText += titleToTransliterate[i]; break;
        }
    }
    return translitedText;
}
/*
$(document).on('blur', 'input[name="custom-property-title"]', function () {
    let inputElem = $(this);
    let newPropertyTitle = inputElem.val();

    if (!newPropertyTitle) {
        return;
    }

    let newPropertyCode = getUniquePropertyCode(newPropertyTitle);
    addCustomPropCodeToSelectAttributes(newPropertyCode, inputElem);
});

$('#submit-form').submit(function (formEvent) {
    let formElem = formEvent.currentTarget;
    formEvent.preventDefault();
    setCustomProperties();
    addParamsToSetupFieldsList();

    BX.ajax.runAction('intaro:retailcrm.api.customexportprops.save', {
        json: {properties: customProps},
    }).then(function() {
        formElem.submit();
    });
});

function addCustomPropCodeToSelectAttributes(customPropCode, customPropTitleElem)
{
    let selectElements = customPropTitleElem.closest('.custom-property-row').find('td select');
    let catalogId = customPropTitleElem.closest('.iblockExportTable').data('type');

    selectElements.each(function (index, element) {
        let selectElem = $(element);
        let newSelectIdValue = selectElem.attr('id').match(/^[^_]*_/)[0] + customPropCode + catalogId;
        let newSelectNameValue = selectElem.attr('name').match(/^[^_]*_/)[0] + customPropCode + `[${catalogId}]`;

        selectElem.attr('id', newSelectIdValue);
        selectElem.attr('name', newSelectNameValue);
        selectElem.data('type', customPropCode);
        triggerSelectChange(selectElem);
    });
}

function triggerSelectChange(selectElem)
{
    if (selectElem.val().length > 0) {
        console.log('был')
        selectElem.trigger('change', [self]);
    }
}

function setCustomProperties()
{
    let customPropertiesRows = $('.custom-property-row');
    let customPropertyCatalogId;
    let customPropertyTitle = '';
    let customPropertyCode = '';
    let productPropertyMatch = '';
    let offerPropertyMatch = '';

    let catalogIds = [];
    customPropertiesRows.each(function (index, propertyRow) {
        let propertyRowObj = $(propertyRow);
        customPropertyCatalogId = propertyRowObj.closest('.iblockExportTable').data('type');

        customPropertyTitle = propertyRowObj.find('input[name="custom-property-title"]').val();
        customPropertyCode = getUniquePropertyCode(customPropertyTitle);
        productPropertyMatch = propertyRowObj.find('select[name=custom-product-property-select]').val();
        offerPropertyMatch = propertyRowObj.find('select[name=custom-offer-property-select]').val();

        let values = {
            'title': customPropertyTitle,
            'code': customPropertyCode,
            'productProperty': productPropertyMatch,
            'offerProperty': offerPropertyMatch
        };

        if (catalogIds.indexOf(customPropertyCatalogId) === -1) {
            customProps[customPropertyCatalogId] = [values];
        } else {
            customProps[customPropertyCatalogId].push(values);
        }
        catalogIds.push(customPropertyCatalogId);
    });
}

function getUniquePropertyCode(customPropertyTitle)
{
    let uniqueValue = transliterate(customPropertyTitle).replace(/ /g, '_');
    let counter = 0;

    const setupFieldsListValues = setupFieldsListElement.val().split(',');
    while (setupFieldsListValues.includes(uniqueValue)) {
        uniqueValue = `${customPropertyTitle}${++counter}`;
    }

    return uniqueValue;
}

function addParamsToSetupFieldsList()
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

    for (let propertiesByCatalogId of Object.values(customProps)) {
        propertiesByCatalogId.forEach(function (values) {
            parametersToFill.forEach(function (param) {
                newParams += ',' + param + values.code;
            });
        });
    }

    let newValue = setupFieldsListElement.val() + newParams;
    setupFieldsListElement.val(newValue);
}

function createCustomPropsRaw(addRowButton)
{
    let templateRow = $($('#custom-property-template-row').html());
    let templateSelectElements = templateRow.find('select');

    let prevTableRow = $(addRowButton).prev('table').find('tbody tr:last-child');
    let lastRawSelectElements = prevTableRow.find('td select');

    lastRawSelectElements.each(function (index, element) {
        let selectElement = $(element);
        let templateSelectElement = templateSelectElements[index];
        fillTemplateSelect(selectElement, templateSelectElement);
        prevTableRow.after(templateRow);
    });
}

function deleteCustomPropsRaw(buttonEvent)
{
    buttonEvent.closest('tr').remove();
}

function fillTemplateSelect(sourceSelectElement, templateSelectElement)
{
    let selectOptions = sourceSelectElement.find('option');
    selectOptions.each(function (index, element) {
        let optionElem = $(element);
        let value = $(optionElem).val();
        let text = $(optionElem).text();

        $('<option>', { value: value, text: text }).appendTo(templateSelectElement);
    });
}

function transliterate(titleToTransliterate)
{
    const hasCyrillicChars = /[\u0400-\u04FF]/.test(titleToTransliterate);

    if (!hasCyrillicChars) {
        return titleToTransliterate;
    }

    translitedText = '';
    for (var i = 0; i < titleToTransliterate.length; i++) {
        switch (titleToTransliterate[i]) {
            case 'а': case 'А': translitedText += 'a'; break;
            case 'б': case 'Б': translitedText += 'b'; break;
            case 'в': case 'В': translitedText += 'v'; break;
            case 'г': case 'Г': translitedText += 'g'; break;
            case 'д': case 'Д': translitedText += 'd'; break;
            case 'е': case 'Е': translitedText += 'e'; break;
            case 'ё': case 'Ё': translitedText += 'yo'; break;
            case 'ж': case 'Ж': translitedText += 'zh'; break;
            case 'з': case 'З': translitedText += 'z'; break;
            case 'и': case 'И': translitedText += 'i'; break;
            case 'й': case 'Й': translitedText += 'y'; break;
            case 'к': case 'К': translitedText += 'k'; break;
            case 'л': case 'Л': translitedText += 'l'; break;
            case 'м': case 'М': translitedText += 'm'; break;
            case 'н': case 'Н': translitedText += 'n'; break;
            case 'о': case 'О': translitedText += 'o'; break;
            case 'п': case 'П': translitedText += 'p'; break;
            case 'р': case 'Р': translitedText += 'r'; break;
            case 'с': case 'С': translitedText += 's'; break;
            case 'т': case 'Т': translitedText += 't'; break;
            case 'у': case 'У': translitedText += 'u'; break;
            case 'ф': case 'Ф': translitedText += 'f'; break;
            case 'х': case 'Х': translitedText += 'h'; break;
            case 'ц': case 'Ц': translitedText += 'c'; break;
            case 'ч': case 'Ч': translitedText += 'ch'; break;
            case 'ш': case 'Ш': translitedText += 'sh'; break;
            case 'щ': case 'Щ': translitedText += 'sch'; break;
            case 'ъ': case 'Ъ': translitedText += ''; break;
            case 'ы': case 'Ы': translitedText += 'y'; break;
            case 'ь': case 'Ь': translitedText += ''; break;
            case 'э': case 'Э': translitedText += 'e'; break;
            case 'ю': case 'Ю': translitedText += 'yu'; break;
            case 'я': case 'Я': translitedText += 'ya'; break;
            default: translitedText += titleToTransliterate[i]; break;
        }
    }
    return translitedText;
}*/
