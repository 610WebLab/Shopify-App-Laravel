import React from 'react'
import { ButtonGroup, IndexTable, useIndexResourceState, Button, LegacyCard } from '@shopify/polaris';
import { useState, useCallback, useEffect } from 'react';
import TableRow from './TableRow';

import { getTableRow ,addTableRow ,updateTableRow ,deleteTableRow } from './TableRowApi';

const ShippingRates = ({TableRateID, changeCalculationType}) => {

    const [disabledLabel, setDisabledLabel] = useState(false);
    const [disabledItemCost, setDisabledItemCost] = useState(false);

    useEffect(()=>{
        if(changeCalculationType == "per_order")
        {
            setDisabledLabel(false);
            setDisabledItemCost(false);
        } 
        else if(changeCalculationType == "item")
        {
            setDisabledLabel(true);
            setDisabledItemCost(true);
        }
        else
        {
            setDisabledLabel(true);
            setDisabledItemCost(false);
        }
    },[changeCalculationType]);

    const [refreshRow , setRefreshRow] = useState(false);

    useEffect(()=>{
        getTableRow(TableRateID).then((res) => {
            if(res.data){
                setShipData(res.data);
            }
        });
    },[refreshRow]);

    const [shipData, setShipData] = useState([]);

    function addTableRatesRows() {
        //setShipData([...shipData, rows]);
        addTableRow({
            "TableRateID" : TableRateID,
            "shop" : Config.shop
        }).then((res) => {
            if(res.data){
                setShipData([...shipData, res.data]);
            }
        });
    }

    const deleteRows = () =>{
        if(confirm('Are you sure want to delete ?')){
            deleteTableRow({
                'ids' : selectedResources,
                '_method' : 'DELETE',
            }).then( (res) => {
                setRefreshRow(!refreshRow);
            });
        }
    }

    const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(shipData);

    const rowMarkup = shipData.map((rate, index) => (
            <TableRow 
            rate={rate} 
            index={index} 
            refreshRow={refreshRow} 
            disabledLabel={disabledLabel}
            disabledItemCost={disabledItemCost}
            setRefreshRow={setRefreshRow} 
            selectedResources={selectedResources} 
            updateTableRowApi={updateTableRow} 
            deleteTableRowApi={deleteTableRow} 
            />
    ));

    return (
        <div className='shipping-rates-rows-table'>
            <LegacyCard>
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
                        { title: 'Condition (?)' },
                        { title: 'Min–Max (?)' },
                        { title: 'Break (?)' },
                        { title: 'Abort (?)' },
                        { title: 'Row cost (?)' },
                        { title: 'Item cost (?)' },
                        { title: 'lbs cost (?)' },
                        { title: '% cost (?)' },
                        { title: 'Label (?)' },
                        { title: 'Actions' },
                    ]}
                // selectable={false}
                >
                    {rowMarkup}
                </IndexTable>
            </LegacyCard>

            <div className='tableratefooter'>
                <ButtonGroup>
                    {/* <Button onClick={() => deleteRows()}  disabled={(!selectedResources.length)} >Delete Shipping Rates</Button> */}
                    <Button primary onClick={() => addTableRatesRows()}>Add Shipping Rates</Button>
                </ButtonGroup>
            </div>

        </div>
    )
}

export default ShippingRates
