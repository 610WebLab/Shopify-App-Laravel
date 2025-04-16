import React, { useState, useEffect, useCallback } from 'react'
import {
    Page, TextField,
    IndexTable,
    LegacyCard,
    IndexFilters,
    useSetIndexFiltersMode,
    useIndexResourceState,
    Text,
    ChoiceList,
    RangeSlider,
    Badge,
    IndexFiltersMode,
    useBreakpoints,
    Button,
    Icon,
    ButtonGroup,
    Select,
    Modal,
    TextContainer 
} from '@shopify/polaris';
import { DeleteMajor, DeleteMinor } from "@shopify/polaris-icons";

import { useNavigate } from 'react-router-dom';
import { useToast } from '@shopify/app-bridge-react';
const DimensionListing = () => {
    const { show } = useToast();
    const navigate = useNavigate();
    const [loader, setLoader] = useState(true);
    const [current, setCurrent] = useState(null);
    const { mode, setMode } = useSetIndexFiltersMode(IndexFiltersMode.Filtering);
    const [queryValue, setQueryValue] = useState(undefined);
    const handleQueryValueChange = useCallback((value) => setQueryValue(value), []);
    const handleQueryValueRemove = useCallback(() => setQueryValue(''), []);
    const handleFiltersClearAll = useCallback(() => { handleQueryValueRemove(); }, [handleQueryValueRemove]);
    const tabs = [];
    const filters = [];
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [dimensions, setDimensions] = useState([]);
    const [boxSorting, setBoxSorting] = useState([]);
     const [dimensionId, setDimensionId] = useState(0);
    
        const [dimensionDelConfirm, setDimensionDelConfirm] = useState(false); 

        const DeleteConfirmation = useCallback((id) => {
            if (id) {
                setDimensionId(id);
                setDimensionDelConfirm(!dimensionDelConfirm), [dimensionDelConfirm];
            }
        });
    const handleChange = useCallback((value, id) => {
        console.log(value, id, "handleChange");
        // let newValue = value > 0 ? value : ;
        if (id > 0 && value > 0) {
            setLoader(false);
            fetch(`/sort/dimension/${id}`, {
                method: "POST",
                body: JSON.stringify({
                    shop: Config.shop,
                    sorting: value,
                    _token: Config.csrf_token,
                }),
                headers: {
                    "Content-type": "application/json",
                },
            })
                .then((res) => res.json())
                .then((result) => {
                    setLoader(true);
                    if (result.status) {
                        show(result.message, { duration: 2000 });
                        setTimeout(function () { getPackageDimensionData(page, queryValue); }, 2000);
                    } else {
                        show(result.message, { duration: 2000, isError: true });
                    }
                });
        }
        setBoxSorting((prev) => ({
            ...prev,
            [id]: parseInt(value)
        }));

    }, []);
    useEffect(() => {
        const defaultt = dimensions.length > 0 && dimensions.reduce((acc, box) => {
            acc[box.id] = box.sorting ? box.sorting : 1;
            return acc;
        }, {});
        setBoxSorting(defaultt);
    }, [dimensions]);
    /* start pagination  */

    const nextPage = () => {
        if (page < lastPage) {
            setPage(page + 1);
        }
    };

    const prevPage = () => {
        if (page > 1) {
            setPage(page - 1);
        }
    };
    function deleteShippingDimension() {
        let id = (dimensionId != 0) ? dimensionId : 0;
        if (id) {
            fetch("/dimension/" + id, {
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
                if (result.status) {
                    setDimensionDelConfirm(false);
                    getPackageDimensionData(page, queryValue);
                    show(result.data.msg, { duration: 2000 });
                    return false;
                } else {
                    show(result.data.msg, { duration: 2000, isError: true });
                    return false;
                }
            });
        }
    }

    useEffect(() => {
        getPackageDimensionData(page, queryValue);
    }, [page, queryValue]);
    const getPackageDimensionData = (page, queryValue) => {
        setLoader(false);
        fetch("/dimension?shop=" + Config.shop + "&page=" + page + "&search=" + queryValue)
            .then((res) => res.json())
            .then(
                (result) => {
                    setLoader(true)
                    if (result.status) {
                        setDimensions(result.data.data);
                        setLastPage(result.data.last_page);
                        setPage(result.data.current_page)
                        setCurrentPage(result.data.current_page);
                        setTotalPages(result.data.total);
                    } else {
                        console.log(result.message);
                    }
                },
                (error) => {
                    console.log(error);
                    setLoader(true);
                }
            );
    };
    

    const resourceName = {
        singular: 'dimension',
        plural: 'dimensions',
    };

    const { selectedResources, allResourcesSelected, handleSelectionChange } =
        useIndexResourceState(dimensions);

    const rowMarkup = dimensions.length > 0 && dimensions.map(
        (
            { id, name, dimension_unit, length, width, height, weight, weight_unit, currency },
            index,
        ) => (
            <IndexTable.Row
                id={id}
                key={id}
                // selected={selectedResources.includes(id)}
                position={index}
            >
                <IndexTable.Cell>
                    {name}
                    <div className="shipping-zone-btn-action">
                        <Button
                            plain
                            onClick={() => {
                                navigate("/pages/dimension/edit/" + id);
                            }}>
                            Edit
                        </Button>
                        &nbsp;&nbsp;
                        <Button
                            plain
                            destructive
                            onClick={() => {
                                DeleteConfirmation(id);
                            }}>
                            Delete
                        </Button>
                    </div>
                </IndexTable.Cell>
                <IndexTable.Cell>{dimension_unit ? dimension_unit.toUpperCase() : ''}</IndexTable.Cell>
                <IndexTable.Cell>{length}</IndexTable.Cell>
                <IndexTable.Cell> {width}</IndexTable.Cell>
                <IndexTable.Cell>{height}</IndexTable.Cell>
                <IndexTable.Cell>{weight}</IndexTable.Cell>
                <IndexTable.Cell>{weight_unit ? weight_unit.toUpperCase() : ''}</IndexTable.Cell>
                <IndexTable.Cell>
                    <TextField
                        type="number"
                        value={boxSorting[id]}
                        onChange={(value) => { handleChange(value, id) }}
                        autoComplete="off"
                    />
                </IndexTable.Cell>
            </IndexTable.Row>
        ),
    );

    return (
        <>
        <div className="package-dimension-list">
            <Page fullWidth
                title="Box Dimension"
                primaryAction={{
                    content: 'Add New Dimension',
                    onAction: () => navigate('/pages/dimension/create'), // Redirect on click
                }}
            >
                <LegacyCard>
                    <IndexFilters
                        queryValue={queryValue}
                        queryPlaceholder="Searching in all"
                        onQueryChange={handleQueryValueChange}
                        onQueryClear={() => setQueryValue('')}
                        cancelAction={{}}
                        tabs={tabs}
                        canCreateNewView
                        filters={filters}
                        onClearAll={handleFiltersClearAll}
                        mode={mode}
                        setMode={setCurrent}
                    />
                    <IndexTable
                        condensed={useBreakpoints().smDown}
                        resourceName={resourceName}
                        itemCount={dimensions.length}
                        selectedItemsCount={
                            allResourcesSelected ? 'All' : selectedResources.length
                        }
                        onSelectionChange={handleSelectionChange}
                        headings={[
                            { title: 'Name' },
                            { title: 'Dimension Unit' },
                            { title: 'Length' },
                            { title: 'Width' },
                            { title: 'Height' },
                            { title: 'Weight' },
                            { title: 'Weight Unit' },
                            { title: 'Sorting' },
                        ]}
                        pagination={{
                            hasPrevious: currentPage > 1,
                            onPrevious: () => {
                                prevPage();
                            },
                            hasNext: currentPage < totalPages,
                            onNext: () => {
                                nextPage();
                            },
                        }}
                        loading={!loader}
                        selectable={false}
                    >
                        {loader && rowMarkup}
                    </IndexTable>
                </LegacyCard>
            </Page>
        </div>
        <Modal
                        // activator={activator}
            open={dimensionDelConfirm}
            onClose={DeleteConfirmation}
            // title=""
            primaryAction={{
                destructive: 1,
                content: 'Delete',
                onAction: deleteShippingDimension,
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
        </Modal></>
    )
}

export default DimensionListing
