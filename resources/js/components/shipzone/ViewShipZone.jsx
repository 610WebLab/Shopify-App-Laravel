import React from 'react'
import { IndexTable, LegacyCard, Icon, Page, Button } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
import { DeleteMajor, EditMajor } from "@shopify/polaris-icons";
const ViewShipZone = () => {
    const [viewShip, setViewShipt] = useState([]);
    const [active, setActive] = useState(false);

    const [apiResObj, setApiResObj] = useState({
        status: false,
        msg: ""
    });
    const toggleActive = useCallback(() => setActive((active) => !active), []);
    const toastMarkup = active ? (<Toast content={apiResObj.msg} error={!apiResObj.status} onDismiss={toggleActive} />) : null;
    useEffect(() => {
        getShipZoneData();
    }, [])

    const getShipZoneData = () => {
        fetch("/shipzone?shop=" + Config.shop).then(res => res.json()).then((result) => {
            console.log("viewShipZone", result)
            if (result.status === 1) {

                console.log("ZoneName", result)
                setViewShipt(result.shopZone);
            }
            // setIsLoaded(true);
        }, (error) => {
            setApiResObj({
                status: false,
                msg: "Something went wrong."
            });
        })
    }
    const resourceName = {
        singular: 'viewShip',
        plural: 'viewShips',
    };
    const rowMarkup = viewShip.map(
        (ship, index) => (
            <IndexTable.Row id={ship.id} key={ship.id} position={index}>
                <IndexTable.Cell>{ship.ship_zone}</IndexTable.Cell>
                <IndexTable.Cell>{ship.zone_region}</IndexTable.Cell>
                <IndexTable.Cell>{ship.method.map(m => (m.ship_method == "flat_rate") ? "Flat Rate" : (m.ship_method == "free_shipping") ? "Free Shipping" : (m.ship_method == "local_pickup") ? "Local Pickup" : "Table Rates").join(', ')}</IndexTable.Cell>
                <IndexTable.Cell>
                    <div className="actions-links">
                        <a href="#" onClick="" >
                            <Icon source={EditMajor} color="base" />
                        </a>
                        <a href="#" onClick="" >
                            <Icon source={DeleteMajor} color="critical" />
                        </a>
                    </div>
                </IndexTable.Cell>
            </IndexTable.Row>
        ),
    );
    return (
        <LegacyCard>
            <IndexTable
                resourceName={resourceName}
                itemCount={viewShip.length}
                headings={[
                    { title: 'Zone Name' },
                    { title: 'Region(s)' },
                    { title: 'Shipping Method(s)' },
                ]}
                selectable={false}
            >
                {rowMarkup}
            </IndexTable>
        </LegacyCard>
    )
}

export default ViewShipZone
