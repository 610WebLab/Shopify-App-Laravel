import React from 'react'
import { IndexTable, LegacyCard, Button, TextField, Page, DataTable, ButtonGroup  } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
import AddShippingClass from './shipclass/AddShippingClass';

const ShippingClass = () => {
    let rows = {
        id: "",
        shipClass: "",
        shipSlug: "",
        shipDesc: "",
        count: 0
    };

    const [rowsData, setRowsData] = useState([{
        id: "",
        shipClass: "",
        shipSlug: "",
        shipDesc: "",
        count: 0
    }]);

    const rowMarkup = <AddShippingClass shipClass={rowsData}></AddShippingClass>

    function addNewComponent() {
        setRowsData([...rowsData, rows]);
    }

    return (
        <div>
            <Page fullWidth
                title="Shipping Classes"
                subtitle=""
                className="shipping-zones"
                fullwidth>
                <LegacyCard>
                    <IndexTable
                        itemCount={rowsData.length}
                        headings={[
                            { title: 'Shipping class' },
                            { title: 'Slug' },
                            { title: 'Description' },
                            { title: 'Product count' },
                        ]}
                        selectable={false}
                    >
                        {rowMarkup}
                    </IndexTable>
                </LegacyCard>
                <div className='tableshippingclass'>
                    <ButtonGroup>
                        <Button primary>Save Shipping Classes</Button>
                        <Button  onClick={() => addNewComponent()}>Add Shipping Class</Button>
                    </ButtonGroup>
                </div>
            </Page>
        </div>
    )
}

export default ShippingClass
