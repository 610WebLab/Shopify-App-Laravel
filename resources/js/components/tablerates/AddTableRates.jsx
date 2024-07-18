import * as React from 'react'
import { Page, VerticalStack, FormLayout, TextField, Select, Tooltip, Card, Loading, Text, Button } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
import { useLocation, Link, useNavigate, useParams } from "react-router-dom";
import { ContextualSaveBar, Toast } from '@shopify/app-bridge-react';


import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
import ShippingRates from './ShippingRates';

import TableRatesApi, { updateTableRate } from './TableRatesApi';
import SimpleReactValidator from 'simple-react-validator';

const AddTableRates = ({ TableRateID, setChangeCalculationType }) => {

    // const { TableRateID } = useParams();

    const navigate = useNavigate();

    const [isLoaded, setIsLoaded] = useState(false);
    const [isFullscreen, setFullscreen] = useState(true);
    const [showSavebar, setShowSavebar] = useState(false);
    const [zoneId, setZoneId] = useState(0);
    const [apiResObj, setApiResObj] = useState({
        status: false,
        msg: ""
    });
    const validator = useRef(new SimpleReactValidator());
    const [defaultData, setDefaultData] = useState({
        "title": "",
        "tax_status": "taxable",
        "taxship_include": "yes",
        "handling_fee": 0,
        "maxship_cost": 0,
        "calculation_type": "per_order",
        "handlingfee_peritem": 0,
        "mincost_peritem": 0,
        "maxcost_peritem": 0,
        "dicount_minmax": false,
        "tax_minmax": false,
        "status": 0,
    });

    useEffect(() => {
        if (TableRateID) {
            getTableRateShip();
        }
    }, [TableRateID])



    async function getTableRateShip() {
        setIsLoaded(false);
        const response = await fetch('/v1/table-rate-shipping/' + TableRateID);
        const result = await response.json();
        console.log("results", result)
        if (result.status && result.tableRate) {
            let e = result.tableRate;
            setMethodTitle(e.title);
            setValueDesc(e.description)
            setSelectedTax(e.tax_status);
            setSelectedShipTax(e.taxship_include);
            setHandlingFee(e.handling_fee);
            setMaxShipCost(e.maxship_cost);
            setSelectedCalculationType(e.calculation_type);
            setCalCulationTypeText(getCalCulationType[e.calculation_type]);
            setChangeCalculationType(e.calculation_type);
            setHandlingFeePerOrder(e.handlingfee_peritem);
            setMinimumCostPerOrder(e.mincost_peritem);
            setMaximumCostPerOrder(e.maxcost_peritem);
            setDiscountChecked(e.dicount_minmax);
            setTaxMinChecked(e.tax_minmax);
            setZoneId(result.tableRate.zone_id);
            setIsLoaded(true);
        }
    }



    const [methodTitle, setMethodTitle] = useState(defaultData.title);
    const handleMethodTitleChange = useCallback((value) => { setMethodTitle(value); setShowSavebar(true); }, []);

    const [valueDesc, setValueDesc] = useState('');
    const handleDescChange = useCallback((newValue) => setValueDesc(newValue), []);

    const [selectedTax, setSelectedTax] = useState(defaultData.tax_status);
    const handleSelectTaxChange = useCallback((value) => { setSelectedTax(value); setShowSavebar(true); }, []);
    const taxOptions = [
        { label: 'Taxable', value: 'taxable' },
        { label: 'None', value: 'none' },
    ];

    const [selectedShipTax, setSelectedShipTax] = useState(defaultData.taxship_include);
    const handleSelectShipTaxChange = useCallback((value) => { setSelectedShipTax(value); setShowSavebar(true); }, []);
    const shipTaxOptions = [
        { label: 'Yes, I Will Enter Costs Below Inclusive Of Tax', value: 'yes' },
        { label: 'No, I Will Enter Costs Below Exclusive Of Tax', value: 'no' },
    ];

    const [handlingFee, setHandlingFee] = useState(defaultData.handling_fee);
    const handleHandlingFeeChange = useCallback((value) => { setHandlingFee(value); setShowSavebar(true); }, [])

    const [maxShipCost, setMaxShipCost] = useState(defaultData.maxship_cost);
    const handleMaxShipCostChange = useCallback((value) => { setMaxShipCost(value); setShowSavebar(true); }, [])

    const [calCulationTypeText, setCalCulationTypeText] = useState("Order");
    const getCalCulationType = {
        'per_order': 'Order',
        'item': 'Item',
        'line': 'Line Item',
    };
    const [disabledButton, setDisabledButton] = useState(true);
    const [selectedCalculationType, setSelectedCalculationType] = useState(defaultData.calculation_type);
    const handleCalculationTypeChange = useCallback((value) => {
        // (value == "per_order") ? setCalCulationTypeText("Order") : (value == "item") ? setCalCulationTypeText("Item") : setCalCulationTypeText("Line Item");
        setCalCulationTypeText(getCalCulationType[value]);
        setChangeCalculationType(value);
        setSelectedCalculationType(value); setShowSavebar(true);
    }, []);
    const calculationTypeOptions = [
        { label: 'Per order', value: 'per_order' },
        { label: 'Calculated Rates Per Item', value: 'item' },
        { label: 'Calculated Rates Per Line Item', value: 'line' },
        // { label: 'Calculated Rates Per Shipping Class', value: 'class' },
    ];

    const [handlingFeePerOrder, setHandlingFeePerOrder] = useState(defaultData.handlingfee_peritem);
    const handleHandlingFeePerOrderChange = useCallback((value) => { setHandlingFeePerOrder(value); setShowSavebar(true); }, [])

    const [minimumCostPerOrder, setMinimumCostPerOrder] = useState(defaultData.mincost_peritem);
    const handleMinimumCostPerOrderChange = useCallback((value) => { setMinimumCostPerOrder(value); setShowSavebar(true); }, [])

    const [maximumCostPerOrder, setMaximumCostPerOrder] = useState(defaultData.maxcost_peritem);
    const handleMaxiimumCostPerOrderChange = useCallback((value) => { setMaximumCostPerOrder(value); setShowSavebar(true); }, [])

    const [discountChecked, setDiscountChecked] = useState(defaultData.dicount_minmax);
    const handleDiscountChange = useCallback((newChecked) => { setDiscountChecked(newChecked); setShowSavebar(true); }, []);

    const [taxMinChecked, setTaxMinChecked] = useState(defaultData.tax_minmax);
    const handleTaxMinChange = useCallback((newChecked) => { setTaxMinChecked(newChecked); setShowSavebar(true); }, []);

    const saveAction = () => {

        // disabled: false,
        // loading: false,
        // onAction: () => {

        if (validator.current.allValid()) {

            saveAction.loading = true;

            let saveData = {
                "shop": Config.shop,
                "_method": "PUT",
                "id": TableRateID,
                "title": methodTitle,
                'description': valueDesc,
                "tax_status": selectedTax,
                "taxship_include": selectedShipTax,
                "handling_fee": handlingFee,
                "maxship_cost": maxShipCost,
                "calculation_type": selectedCalculationType,
                "handlingfee_peritem": handlingFeePerOrder,
                "mincost_peritem": minimumCostPerOrder,
                "maxcost_peritem": maximumCostPerOrder,
                "dicount_minmax": discountChecked,
                "tax_minmax": taxMinChecked,
            };
            updateTableRate(saveData).then((res) => {
                setShowSavebar(false);
                saveAction.loading = false;
                setApiResObj(res);
            });
        } else {
            validator.current.showMessages();
        }
        // }
    };
    const discardAction = {
        disabled: false,
        loading: false,
        discardConfirmationModal: true,
        onAction: () => console.log('On discard action')
    };


    return (
        <>
            {!isLoaded && <Loading />}
            <Page
                fullWidth
                backAction={{content: 'Settings', onAction: (() => { navigate('/new/' + zoneId); })}} title="Table Rate"
            >
                {/* <ContextualSaveBar
                saveAction={saveAction}
                discardAction={discardAction}
                fullWidth
                leaveConfirmationDisable
                visible={showSavebar}
            /> */}

                <VerticalStack gap="4">

                <Card padding="4">
                        <FormLayout>
                            <Tooltip content="This controls the title which the user see the checkout.">
                                <TextField
                                    label="Method Title"
                                    value={methodTitle}
                                    onChange={handleMethodTitleChange}
                                    // placeholder="Example: North America, Europe"
                                    error={validator.current.message('title', methodTitle, 'required')}
                                    onBlur={() => validator.current.showMessageFor('title')}
                                    autoComplete="off"
                                />
                            </Tooltip>
                            <TextField
                                label="Method Description"
                                value={valueDesc}
                                onChange={handleDescChange}
                                autoComplete="off"
                            />

                            <Select
                                label="Tax Status"
                                options={taxOptions}
                                onChange={handleSelectTaxChange}
                                value={selectedTax}
                            />
                            <Select
                                label="Tax Included In Shipping Costs"
                                options={shipTaxOptions}
                                onChange={handleSelectShipTaxChange}
                                value={selectedShipTax}
                            />
                            <Tooltip content="Enter an amount, e.g. 2.50. Leave blank to disable. This cost is applied once for the order as whole">
                                <TextField
                                    label="Handling Fee"
                                    value={handlingFee}
                                    onChange={handleHandlingFeeChange}
                                    placeholder="n/a"
                                    autoComplete="off"
                                    error={validator.current.message('Handling Fee', handlingFee, 'required|numeric|min:0,num')}
                                    onBlur={() => validator.current.showMessageFor('Handling Fee')}
                                />
                            </Tooltip>
                            <Tooltip content="Maximum cost that the customer will pay after all the shipping rules have been applied. If the shipping cost calculated is bigger than this value, this cost will be the one shown.">
                                <TextField
                                    label="Maximum Shipping Cost"
                                    value={maxShipCost}
                                    onChange={handleMaxShipCostChange}
                                    placeholder="n/a"
                                    autoComplete="off"
                                    error={validator.current.message('Maximum Shipping Cost', maxShipCost, 'required|numeric|min:0,num')}
                                    onBlur={() => validator.current.showMessageFor('Maximum Shipping Cost')}
                                />
                            </Tooltip>
                            <label>Rates
                                <p>This is where you define your table rates which are applied to an order.</p>
                            </label>
                            <Tooltip content="Per order rates will offer the customer all matching rates. Calculated will sum of all matching rates and provide the single total.">
                                <Select
                                    label="Calculation Type"
                                    options={calculationTypeOptions}
                                    onChange={handleCalculationTypeChange}
                                    value={selectedCalculationType}
                                />
                            </Tooltip>
                            <TextField
                                label={`Handling Fee Per ${calCulationTypeText}`}
                                value={handlingFeePerOrder}
                                onChange={handleHandlingFeePerOrderChange}
                                placeholder="n/a"
                                autoComplete="off"
                                error={validator.current.message('Handling Fee Per Order', handlingFee, 'required|numeric|min:0,num')}
                                onBlur={() => validator.current.showMessageFor('Handling Fee Per Order')}
                            />
                            <TextField
                                label={`Minimum Cost Per ${calCulationTypeText}`}
                                value={minimumCostPerOrder}
                                onChange={handleMinimumCostPerOrderChange}
                                placeholder="n/a"
                                autoComplete="off"
                                error={validator.current.message('Minimum Cost Per Order', handlingFee, 'required|numeric|min:0,num')}
                                onBlur={() => validator.current.showMessageFor('Minimum Cost Per Order')}
                            />
                            <TextField
                                label={`Maximum Cost Per ${calCulationTypeText}`}
                                value={maximumCostPerOrder}
                                onChange={handleMaxiimumCostPerOrderChange}
                                placeholder="n/a"
                                autoComplete="off"
                                error={validator.current.message('Maximum Cost Per Order', handlingFee, 'required|numeric|min:0,num')}
                                onBlur={() => validator.current.showMessageFor('Maximum Cost Per Order')}
                            />
                            {/* <label>Discounts in Min-Max</label>
                        <Tooltip content="When comparing Min-Max Price Condition for rate in Table Rates, ser if discounted or non-discounted price should be used.">
                            <Checkbox
                                label="Use discounted price when comparing Min-Max Price Conditions."
                                checked={discountChecked}
                                onChange={handleDiscountChange}
                            />
                        </Tooltip>
                        <label>Taxes in Min-Max</label>
                        <Tooltip content="When comparing Min-Max Price Condition for rate in Table Rates, set if price with taxes or without taxes should be used.">
                            <Checkbox
                                label=" Use price with tax when comparing Min-Max Price Conditions."
                                checked={taxMinChecked}
                                onChange={handleTaxMinChange}
                            />
                        </Tooltip> */}
                            {/* <label>Table Rates</label> */}

                            <Button primary onClick={saveAction}>Save Changes</Button>

                        </FormLayout>
                    </Card>

                    {/* <LegacyCard.Section>
                    <ShippingRates TableRateID={TableRateID}  />
                </LegacyCard.Section> */}


                </VerticalStack>


                {apiResObj.msg && <Toast content={apiResObj.msg} duration={2000} isError={!apiResObj.status} onDismiss={() => {
                    setApiResObj({
                        status: 0,
                        msg: ""
                    });
                }} />}

            </Page>
        </>
    )
}

export default AddTableRates
