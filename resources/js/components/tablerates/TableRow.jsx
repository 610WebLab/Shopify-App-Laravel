import React from 'react'
import { useState, useCallback, useEffect } from 'react';
import { TextField, Select, Checkbox, IndexTable ,ButtonGroup,Button } from '@shopify/polaris';
import { useToast} from '@shopify/app-bridge-react';

const TableRow = ({ 
    rate, 
    index , 
    selectedResources ,
    updateTableRowApi ,
    deleteTableRowApi,
    refreshRow, 
    setRefreshRow,
    disabledItemCost,
    disabledLabel,
 }) => {
    const {show} = useToast();
    const [tableRateConditon, setTableRateConditon] = useState(rate.condition);
    const handleTableRateConditonChange = useCallback((newValue) => setTableRateConditon(newValue), []);

    const tableRateConditonnOptions = [
        { label: 'None', value: 'none' },
        { label: 'Price', value: 'price' },
        { label: 'Weight', value: 'weight' },
        { label: 'Item Count', value: 'itemcount' }
    ];

    const [minValue, setMinValue] = useState(rate.ship_min);
    const handleMinValChange = useCallback((newValue) => setMinValue(newValue), []);

    const [maxValue, setMaxValue] = useState(rate.ship_max);
    const handleMaxValChange = useCallback((newValue) => setMaxValue(newValue), []);

    const [breakChecked, setBreakChecked] = useState(rate.ship_break);
    const handleBreakChange = useCallback((newChecked) => {   setBreakChecked(newChecked) } , []);

    const [abortChecked, setAbortChecked] = useState(rate.ship_abort);
    const handleAbortChange = useCallback((newChecked) => { setAbortChecked(newChecked)}, []);

    const [rowCostVal, setRowCostVal] = useState(rate.row_cost);
    const handleRowCostValChange = useCallback((newValue) => { (newValue >= 0) ? setRowCostVal(newValue) : setRowCostVal(0) }, [] );

    const [itmeCostVal, setItmeCostVal] = useState(rate.item_cost);
    const handleItmeCostValChange = useCallback((newValue) => { (newValue >= 0) ? setItmeCostVal(newValue) : setItmeCostVal(0) }, [] );

    const [lbsCostVal, setLbsCostVal] = useState(rate.lbs_cost);
    const handleLbsCostValChange = useCallback((newValue) => { (newValue >= 0) ? setLbsCostVal(newValue) : setLbsCostVal(0) }, [] );

    const [percentCost, setPercentCost] = useState(rate.percent_cost);
    const handlePercentCostChange = useCallback((newValue) => { (newValue >= 0) ? setPercentCost(newValue) : setPercentCost(0) }, [] );

    const [labelValue, setLabelValue] = useState(rate.lable);
    const handleLabelValueChange = useCallback((newValue) => setLabelValue(newValue), []);


    const saveChanges = () => {
        updateTableRowApi({
            'id' : rate.id,
            '_method' : 'PUT',
            'shop' :Config.shop,
            'ship_class' : 0 ,
            'condition' : tableRateConditon ,
            'ship_min' : minValue ,
            'ship_max' : maxValue ,
            'ship_break' : breakChecked ,
            'ship_abort' : abortChecked ,
            'row_cost' : rowCostVal ,
            'item_cost' : itmeCostVal ,
            'lbs_cost' : lbsCostVal ,
            'percent_cost' : percentCost ,
            'lable': labelValue
        }).then( (res) => {
            if(res.status) {
                show(res.msg, {duration: 2000})
            } else {
                show(res.msg, {duration: 2000, isError: true})
            }
        });
    };

    const deleteRow = () =>{
        if(confirm('Are you sure want to delete ?')){
            deleteTableRowApi({
                'ids' : [rate.id],
                '_method' : 'DELETE',
            }).then( (res) => {
                if(res.status) {
                    show(res.msg, {duration: 2000});
                    setRefreshRow(!refreshRow);
                } else {
                    show(res.msg, {duration: 2000, isError: true});
                }
                
            });
        }
    }

    return(
        <IndexTable.Row
            id={rate.id}
            key={"shiprate1-" + index}
            position={index}
            selected={selectedResources.includes(rate.id)}
            onClick={() => { return false }}
        >
            <IndexTable.Cell key={"shiprate2-" + index}>
                <Select options={tableRateConditonnOptions} onChange={handleTableRateConditonChange} value={tableRateConditon} />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate3-" + index}>
                <div className="tablerateminmax">
                    <TextField value={minValue} onChange={handleMinValChange} autoComplete="off" disabled={(tableRateConditon == 'none')} />&nbsp;
                    <TextField value={maxValue} onChange={handleMaxValChange} autoComplete="off" disabled={(tableRateConditon == 'none')} />
                </div>
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate4-" + index}>
                <Checkbox checked={breakChecked} onChange={handleBreakChange} />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate5-" + index}>
                <Checkbox checked={abortChecked} onChange={handleAbortChange} />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate6-" + index}>
                <TextField type="number" value={rowCostVal} onChange={handleRowCostValChange} autoComplete="off" />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate7-" + index}>
                <TextField type="number" disabled={disabledItemCost} value={itmeCostVal} onChange={handleItmeCostValChange} autoComplete="off" />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate8-" + index}>
                <TextField type="number" value={lbsCostVal} onChange={handleLbsCostValChange} autoComplete="off" />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate9-" + index}>
                <TextField type="number" value={percentCost} onChange={handlePercentCostChange} autoComplete="off" />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate0-" + index}>
                <TextField value={labelValue} disabled={disabledLabel} onChange={handleLabelValueChange} autoComplete="off" />
            </IndexTable.Cell>

            <IndexTable.Cell key={"shiprate0-" + index}>
                <ButtonGroup>
                    <Button primary size="slim" onClick={()=>{saveChanges()}} > Save</Button>
                    <Button destructive size="slim" onClick={()=>{ deleteRow() }} > Delete</Button>
                </ButtonGroup>
            </IndexTable.Cell>

        </IndexTable.Row>
    );
    
}

export default TableRow
