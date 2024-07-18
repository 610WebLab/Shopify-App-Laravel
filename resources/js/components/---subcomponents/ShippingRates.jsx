import React from 'react'
import { FormLayout, TextField, Select, Tooltip, Checkbox, ButtonGroup, IndexTable, useIndexResourceState, DataTable, Button, LegacyCard } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
import TableRow from './TableRow'

const ShippingRates = () => {

    const [tableRateConditon, setTableRateConditon] = useState('');
    const handleTableRateConditonChange = useCallback((value) => setTableRateConditon(value), []);
    const tableRateConditonnOptions = [
        { label: 'None', value: '' },
        { label: 'Price', value: 'price' },
        { label: 'Weight', value: 'weight' },
        { label: 'Item Count', value: 'itemcount' }
    ];

    const [minValue, setMinValue] = useState('');
    const handleMinValChange = useCallback((newValue) => setMinValue(newValue), []);

    const [maxValue, setMaxValue] = useState('');
    const handleMaxValChange = useCallback((newValue) => setMaxValue(newValue), []);

    const [breakChecked, setBreakChecked] = useState(false);
    const handleBreakChange = useCallback((newChecked) => setBreakChecked(newChecked), []);

    const [abortChecked, setAbortChecked] = useState(false);
    const handleAbortChange = useCallback((newChecked) => setAbortChecked(newChecked), []);

    const [rowCostVal, setRowCostVal] = useState(0);
    const handleRowCostValChange = useCallback((newValue) => { console.log(rowCostVal, parseFloat(newValue)); setRowCostVal(parseFloat(newValue)) }, [rowCostVal]);

    const [itmeCostVal, setItmeCostVal] = useState('');
    const handleItmeCostValChange = useCallback((newValue) => { (newValue > 0) ? setItmeCostVal(newValue) : setItmeCostVal(0), [] });

    const [lbsCostVal, setLbsCostVal] = useState('');
    const handleLbsCostValChange = useCallback((newValue) => { (newValue > 0) ? setLbsCostVal(newValue) : setLbsCostVal(0), [] });

    const [percentCost, setPercentCost] = useState('');
    const handlePercentCostChange = useCallback((newValue) => { (newValue > 0) ? setPercentCost(newValue) : setPercentCost(0), [] });

    const [labelValue, setLabelValue] = useState('');
    const handleLabelValueChange = useCallback((newValue) => setLabelValue(newValue), []);

    const customers = {
        condition: <Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon} onClick={() => console.log(`ii`)} />,
        minmax: <div className="tablerateminmax"><TextField value={minValue} onChange={handleMinValChange} autoComplete="off" disabled />&nbsp;
            <TextField value={maxValue} onChange={handleMaxValChange} autoComplete="off" disabled /></div>,
        braek: <Checkbox checked={breakChecked} onChange={handleBreakChange} />,
        abort: <Checkbox checked={abortChecked} onChange={handleAbortChange} />,
        rowcost: <TextField type="number" value={rowCostVal} onChange={handleRowCostValChange} autoComplete="off" />,
        itemcost: <TextField type="number" value={itmeCostVal} onChange={handleItmeCostValChange} autoComplete="off" />,
        lbscost: <TextField type="number" value={lbsCostVal} onChange={handleLbsCostValChange} autoComplete="off" />,
        percent: <TextField type="number" value={percentCost} onChange={handlePercentCostChange} autoComplete="off" />,
        label: <TextField value={labelValue} onChange={handleLabelValueChange} autoComplete="off" />,

    };
    const rows = [
            <Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon} onClick={() => console.log(`ii`)} />,
            <div className="tablerateminmax"><TextField value={minValue} onChange={handleMinValChange} autoComplete="off" disabled />&nbsp;
                <TextField value={maxValue} onChange={handleMaxValChange} autoComplete="off" disabled /></div>,
            <Checkbox checked={breakChecked} onChange={handleBreakChange} />,
            <Checkbox checked={abortChecked} onChange={handleAbortChange} />,
            <TextField type="number" value={rowCostVal} onChange={handleRowCostValChange} autoComplete="off" />,
            <TextField type="number" value={itmeCostVal} onChange={handleItmeCostValChange} autoComplete="off" />,
            <TextField type="number" value={lbsCostVal} onChange={handleLbsCostValChange} autoComplete="off" />,
            <TextField type="number" value={percentCost} onChange={handlePercentCostChange} autoComplete="off" />,
            <TextField value={labelValue} onChange={handleLabelValueChange} autoComplete="off" />

        ];

    const [shipData, setShipData] = useState([
        [
            <Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon} onClick={() => console.log(`ii`)} />,
            <div className="tablerateminmax"><TextField value={minValue} onChange={handleMinValChange} autoComplete="off" disabled />&nbsp;
                <TextField value={maxValue} onChange={handleMaxValChange} autoComplete="off" disabled /></div>,
            <Checkbox checked={breakChecked} onChange={handleBreakChange} />,
            <Checkbox checked={abortChecked} onChange={handleAbortChange} />,
            <TextField type="number" value={rowCostVal} onChange={handleRowCostValChange} autoComplete="off" />,
            <TextField type="number" value={itmeCostVal} onChange={handleItmeCostValChange} autoComplete="off" />,
            <TextField type="number" value={lbsCostVal} onChange={handleLbsCostValChange} autoComplete="off" />,
            <TextField type="number" value={percentCost} onChange={handlePercentCostChange} autoComplete="off" />,
            <TextField value={labelValue} onChange={handleLabelValueChange} autoComplete="off" />

        ]
        // {
        //     condition: <Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon} onClick={() => console.log(`ii`)} />,
        //     minmax: <div className="tablerateminmax"><TextField value={minValue} onChange={handleMinValChange} autoComplete="off" disabled />&nbsp;
        //         <TextField value={maxValue} onChange={handleMaxValChange} autoComplete="off" disabled /></div>,
        //     braek: <Checkbox checked={breakChecked} onChange={handleBreakChange} />,
        //     abort: <Checkbox checked={abortChecked} onChange={handleAbortChange} />,
        //     rowcost: <TextField type="number" value={rowCostVal} onChange={handleRowCostValChange} autoComplete="off" />,
        //     itemcost: <TextField type="number" value={itmeCostVal} onChange={handleItmeCostValChange} autoComplete="off" />,
        //     lbscost: <TextField type="number" value={lbsCostVal} onChange={handleLbsCostValChange} autoComplete="off" />,
        //     percent: <TextField type="number" value={percentCost} onChange={handlePercentCostChange} autoComplete="off" />,
        //     label: <TextField value={labelValue} onChange={handleLabelValueChange} autoComplete="off" />,

        // },
    ])
    const resourceName = {
        singular: 'customer',
        plural: 'customers',
    };

    function addTableRatesRows() {
        setShipData([...shipData, rows])
    }

    const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(shipData);

    // const promotedBulkActions = [
    //     {
    //         content: 'Edit customers',
    //         onAction: () => console.log('Todo: implement bulk edit'),
    //     },
    // ];
    // const bulkActions = [
    //     {
    //         content: 'Add tags',
    //         onAction: () => console.log('Todo: implement bulk add tags'),
    //     },
    //     {
    //         content: 'Remove tags',
    //         onAction: () => console.log('Todo: implement bulk remove tags'),
    //     },
    //     {
    //         content: 'Delete customers',
    //         onAction: () => console.log('Todo: implement bulk delete'),
    //     },
    // ];

    const rowMarkup = <TableRow rates={shipData} refid={0} />;


    console.log
    return (
        <>
        <LegacyCard>
        <DataTable
          columnContentTypes={[
            'text',
            'numeric',
            'numeric',
            'numeric',
            'numeric',
            'numeric',
            'numeric',
            'numeric',
            'text',
          ]}
          headings={[
            'Condition',
            'Min–Max',
            'Break',
            'Abort',
            'Row cost',
            'Item cost',
            'lbs cost ',
            '% cost',
            'Label',
          ]}
          rows={shipData}
          totals={['', '', '', '', '', '', '', '', <Button>Delete Shipping Rates</Button>]}
          totalsName={{
            singular: <Button primary onClick={() => addTableRatesRows()}>Add Shipping Rates</Button>,
          }}
        />
      </LegacyCard>
            {/* <LegacyCard>
                <IndexTable
                    // resourceName={resourceName}
                    itemCount={shipData.length}
                    selectedItemsCount={
                        allResourcesSelected ? 'All' : selectedResources.length
                    }
                    onSelectionChange={handleSelectionChange}
                    // bulkActions={bulkActions}
                    // promotedBulkActions={promotedBulkActions}
                    headings={[
                        { title: 'Condition [?]' },
                        { title: 'Min–Max [?]' },
                        { title: 'Break [?]' },
                        { title: 'Abort [?]' },
                        { title: 'Row cost [?]' },
                        { title: 'Item cost [?]' },
                        { title: 'lbs cost [?]' },
                        { title: '% cost [?]' },
                        { title: 'Label [?]' },
                    ]}
                // selectable={false}
                >
                    {rowMarkup}
                </IndexTable>
            </LegacyCard>
            <div className='tableratefooter'>
                <ButtonGroup>
                    <Button>Delete Shipping Rates</Button>
                    <Button primary onClick={() => addTableRatesRows()}>Add Shipping Rates</Button>
                </ButtonGroup>
            </div> */}
        </>
    )
}

export default ShippingRates
