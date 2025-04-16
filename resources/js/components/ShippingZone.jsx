import React from 'react'
import { IndexTable, LegacyCard, Modal, TextContainer, Page, Toast, Icon, Button, Text, Loading } from '@shopify/polaris';
import { DeleteMajor, DeleteMinor } from "@shopify/polaris-icons";
// import AddShipZone from './shipzone/AddShipZone';
// import AddTableRates from './tablerates/AddTableRates'
// import ViewShipZone from './shipzone/ViewShipZone';
import CheckboxZoneStatus from './CheckboxZoneStatus';
import { useState, useCallback, useRef, useEffect } from 'react';
import { useNavigate } from "react-router-dom";
import { useToast } from '@shopify/app-bridge-react';


const ShippingZone = () => {
    const { show } = useToast();
    const navigate = useNavigate();
    const navigateHome = () => {
        navigate('/new');
        // setIsLoaded(true); 
    };
    const [state, setState] = useState(false);
    const [isLoaded, setIsLoaded] = useState(false);
    const [viewShipState, setViewShipState] = useState(true);
    const [zoneId, setZoneId] = useState(0);

    const [zoneDelConfirm, setZoneDelConfirm] = useState(false);
    // const DeleteConfirmation = useCallback(() => setDelActive(!delActive), [delActive]);
    const DeleteConfirmation = useCallback((id) => {
        if (id) {
            setZoneId(id);
            setZoneDelConfirm(!zoneDelConfirm), [zoneDelConfirm];
        }
    });
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
            if (result.status === 1) {
                setViewShipt(result.shopZone);
            }
            setIsLoaded(true);
        }, (error) => {
            setApiResObj({
                status: false,
                msg: "Something went wrong."
            });
            setIsLoaded(true);
        })
    }




    function addNewComponent() {
        navigateHome();
    }

    function editShippingZone(id) {
        navigate('/new/' + id);
        setState(true);
        setViewShipState(false);
    }
    function deleteShippingZone() {
        let id = (zoneId != 0) ? zoneId : 0;
        if (id) {
            fetch("/shipzone/" + id, {
                method: "POST",
                body: JSON.stringify({
                    shop: Config.shop,
                    _method: 'DELETE',
                    '_token': Config.csrf_token
                }),
                headers: {
                    "Content-type": "application/json"
                }
            }).then(res => res.json()).then((result) => {
                setApiResObj(result);
                if (result.status) {
                    setZoneDelConfirm(false);
                    getShipZoneData();
                    show(result.data.msg, { duration: 2000 });
                    return false;
                } else {
                    show(result.data.msg, { duration: 2000, isError: true });
                    return false;
                }
            });
        }
    }


    const resourceName = {
        singular: 'viewShip',
        plural: 'viewShips',
    };
    function toTitleCase(str) {
        const titleCase = str
            .toLowerCase()
            .split(' ')
            .map(word => {
                return word.charAt(0).toUpperCase() + word.slice(1);
            })
            .join(' ');

        return titleCase;
    }
    const rowMarkup = viewShip.map(
        (ship, index) => (
            <IndexTable.Row id={ship.id} key={ship.id} position={index}>
                <IndexTable.Cell>
                    {ship.ship_zone}
                    <div className="shipping-zone-btn-action">
                        <Button plain onClick={() => { editShippingZone(ship.id) }}>Edit</Button>&nbsp;
                        <Button plain destructive onClick={() => { DeleteConfirmation(ship.id) }} >Delete</Button>
                    </div>
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <CheckboxZoneStatus defaultValue={ship.status} methodId={ship.id} />
                </IndexTable.Cell>
                <IndexTable.Cell><p className='ship-zone-region'>{ship.zone_region}</p></IndexTable.Cell>
                <IndexTable.Cell>
                    <p className='ship-zone-method'>
                        {
                            `${ship.free_shipping_method.map(m => toTitleCase(m.title)).join(', ')}
                            ${ship.local_pickup_method.map(m => toTitleCase(m.title)).join(', ')}
                            ${ship.table_rate_method.map(m => toTitleCase(m.title)).join(', ')}
                            ${ship.flat_rate_method.map(m => toTitleCase(m.title)).join(', ')}
                            ${ship.rate_by_distance.map(m => toTitleCase(m.title)).join(', ')}`
                        }
                    </p>
                </IndexTable.Cell>
            </IndexTable.Row>
        ),
    );

    return (
        <>
            {!isLoaded && <Loading />}
            <div className="shipping-zones">
                <Page fullWidth
                    title="Shipping Zones"
                    subtitle=""
                    className="shipping-zones"
                    primaryAction={{ content: 'Add Shipping Zone', onAction: () => { addNewComponent() } }}
                    fullwidth
                >
                    {/* {(state) ? <AddShipZone editZoneId={editZoneId} /> : ""} */}
                    {/* {!state ? "" : <AddTableRates />} */}
                    {(viewShipState) ?
                        <LegacyCard>
                            <IndexTable
                                resourceName={resourceName}
                                itemCount={viewShip.length}
                                headings={[
                                    { title: 'Zone Name' },
                                    { title: 'Enable/Disable' },
                                    { title: 'Region(s)' },
                                    { title: 'Shipping Method(s)' },
                                ]}
                                selectable={false}
                            >
                                {rowMarkup}
                            </IndexTable>
                        </LegacyCard>
                        : ""
                    }
                    {toastMarkup}
                </Page>
            </div>
            <Modal
                // activator={activator}
                open={zoneDelConfirm}
                onClose={DeleteConfirmation}
                // title=""
                primaryAction={{
                    destructive: 1,
                    content: 'Delete',
                    onAction: deleteShippingZone,
                }}
                secondaryActions={[
                    {
                        content: 'Cencel',
                        onAction: DeleteConfirmation,
                    },
                ]}
            >
                <Modal.Section>
                    <TextContainer>
                            <Icon source={DeleteMinor} color="critical" />
                            <Text variant="headingLg" as="h5">
                                Are you sure ?
                            </Text>
                            <p>
                                You want be able to delete this!
                            </p>
                    </TextContainer>
                </Modal.Section>
            </Modal>
          
        </>
    );
}

export default ShippingZone
