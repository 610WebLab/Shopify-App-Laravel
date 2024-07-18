import * as React from 'react'
import { FormLayout, TextField, Select, Tooltip, Checkbox, ButtonGroup, IndexTable, Text, DataTable, Button, LegacyCard } from '@shopify/polaris';
import { useState, useCallback, useMemo, useEffect } from 'react';
import Table from 'react-bootstrap/Table';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
import ShippingRates from './ShippingRates';

const AddTableRates = () => {
    const [methodTitle, setMethodTitle] = useState('');
    const handleMethodTitleChange = useCallback(
        (value) => setMethodTitle(value),
        [],
    )

    const [selectedTax, setSelectedTax] = useState('taxable');
    const handleSelectTaxChange = useCallback((value) => setSelectedTax(value), []);
    const taxOptions = [
        { label: 'Taxable', value: 'taxable' },
        { label: 'None', value: 'none' },
    ];

    const [selectedShipTax, setSelectedShipTax] = useState('yes');
    const handleSelectShipTaxChange = useCallback((value) => setSelectedShipTax(value), []);
    const shipTaxOptions = [
        { label: 'Yes, I Will Enter Costs Below Inclusive Of Tax', value: 'yes' },
        { label: 'No, I Will Enter Costs Below Exclusive Of Tax', value: 'no' },
    ];

    const [handlingFee, setHandlingFee] = useState('');
    const handleHandlingFeeChange = useCallback(
        (value) => setHandlingFee(value),
        [],
    )

    const [maxShipCost, setMaxShipCost] = useState('');
    const handleMaxShipCostChange = useCallback(
        (value) => setMaxShipCost(value),
        [],
    )

    const [selectedCalculationType, setSelectedCalculationType] = useState('');
    const handleCalculationTypeChange = useCallback((value) => setSelectedCalculationType(value), []);
    const calculationTypeOptions = [
        { label: 'Per order', value: '' },
        { label: 'Calculated Rates Per Item', value: 'item' },
        { label: 'Calculated Rates Per Line Item', value: 'line' },
        { label: 'Calculated Rates Per Shipping Class', value: 'class' },
    ];

    const [handlingFeePerOrder, setHandlingFeePerOrder] = useState('');
    const handleHandlingFeePerOrderChange = useCallback(
        (value) => setHandlingFeePerOrder(value),
        [],
    )

    const [minimumCostPerOrder, setMinimumCostPerOrder] = useState('');
    const handleMinimumCostPerOrderChange = useCallback(
        (value) => setMinimumCostPerOrder(value),
        [],
    )

    const [maximumCostPerOrder, setMaximumCostPerOrder] = useState('');
    const handleMaxiimumCostPerOrderChange = useCallback(
        (value) => setMaximumCostPerOrder(value),
        [],
    )

    const [discountChecked, setDiscountChecked] = useState(false);
    const handleDiscountChange = useCallback((newChecked) => setDiscountChecked(newChecked), []);

    const [taxMinChecked, setTaxMinChecked] = useState(false);
    const handleTaxMinChange = useCallback((newChecked) => setTaxMinChecked(newChecked), []);

    function addTableRatesRows() {
        // tableRatesRows.push(tableRatesRows)

        //     var tblShippingRates = document.getElementById('tableRatesShippingbody');
        //     let index = tblShippingRates.querySelectorAll('tr').length;
        //     let rowLength = tableRatesRows.props.children.length;
        //     // console.log("tableRatesRows", tblShippingRates.querySelectorAll('tr').length, tableRatesRows.props.children.length)
        //     //    let rows = tblShippingRates.append(JSON.stringify(tableRatesRows.props.children));
        //     var rows = tblShippingRates.insertRow(index);
        //     var cell1 = rows.insertCell(0);
        //     var cell2 = rows.insertCell(1);
        //     var cell3 = rows.insertCell(2);
        //     var cell4 = rows.insertCell(3);
        //     var cell5 = rows.insertCell(4);
        //     var cell6 = rows.insertCell(5);
        //     var cell7 = rows.insertCell(6);
        //     var cell8 = rows.insertCell(7);
        //     var cell9 = rows.insertCell(8);

        //     // Add some text to the new cells:
        //     console.log(<Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon}/>)
        //     cell1.innerHTML =(<Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon}/>)
        // //     cell2.innerHTML = <div className="tablerateminmax">
        //     <TextField
        //         value={minValue}
        //         onChange={handleMinValChange}
        //         autoComplete="off"
        //         disabled
        //     />&nbsp;&nbsp;&nbsp;&nbsp;
        //     <TextField
        //         value={maxValue}
        //         onChange={handleMaxValChange}
        //         autoComplete="off"
        //         disabled
        //     />
        // </div>;
        //     cell3.innerHTML = <Checkbox
        //     checked={breakChecked}
        //     onChange={handleBreakChange}
        // />;
        //     cell4.innerHTML =  <Checkbox
        //     checked={abortChecked}
        //     onChange={handleAbortChange}
        // />;
        //     cell5.innerHTML = <TextField
        //     type="number"
        //     value={rowCostVal}
        //     onChange={handleRowCostValChange}
        //     autoComplete="off"
        // />;
        //     cell6.innerHTML = <TextField
        //     type="number"
        //     value={itmeCostVal}
        //     onChange={handleItmeCostValChange}
        //     autoComplete="off"

        // />;
        //     cell7.innerHTML = <TextField
        //     type="number"
        //     value={lbsCostVal}
        //     onChange={handleLbsCostValChange}
        //     autoComplete="off"

        // />;
        //     cell8.innerHTML = <TextField
        //     type="number"
        //     value={percentCost}
        //     onChange={handlePercentCostChange}
        //     autoComplete="off"

        // />;
        //     cell9.innerHTML =  <TextField
        //     value={labelValue}
        //     onChange={handleLabelValueChange}
        //     autoComplete="off"
        // />;
    }
    return (
        <div>
            <FormLayout>
                <Tooltip content="This controls the title which the user see the checkout.">
                    <TextField
                        label="Method Title"
                        value={methodTitle}
                        onChange={handleMethodTitleChange}
                        // placeholder="Example: North America, Europe"
                        autoComplete="off"
                    />
                </Tooltip>
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
                    />
                </Tooltip>
                <Tooltip content="Maximum cost that the customer will pay after all the shipping rules have been applied. If the shipping cost calculated is bigger than this value, this cost will be the one shown.">
                    <TextField
                        label="Maximum Shipping Cost"
                        value={maxShipCost}
                        onChange={handleMaxShipCostChange}
                        placeholder="n/a"
                        autoComplete="off"
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
                    label="Handling Fee Per Order"
                    value={handlingFeePerOrder}
                    onChange={handleHandlingFeePerOrderChange}
                    placeholder="n/a"
                    autoComplete="off"
                />
                <TextField
                    label="Minimum Cost Per Order"
                    value={minimumCostPerOrder}
                    onChange={handleMinimumCostPerOrderChange}
                    placeholder="n/a"
                    autoComplete="off"
                />
                <TextField
                    label="Maximum Cost Per Order"
                    value={maximumCostPerOrder}
                    onChange={handleMaxiimumCostPerOrderChange}
                    placeholder="n/a"
                    autoComplete="off"
                />
                <label>Discounts in Min-Max</label>
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
                </Tooltip>
                <label>Table Rates</label>
                <ShippingRates />
                
            </FormLayout>
        </div>
    )
}

export default AddTableRates
