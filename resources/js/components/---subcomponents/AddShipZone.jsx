import React from 'react'
import { FormLayout, TextField, Stack, Tag, Listbox, EmptySearchResult, Combobox, Text, DataTable, Button, LegacyCard } from '@shopify/polaris';

import { useState, useCallback, useMemo, useEffect } from 'react';
import AddShipMethod from './AddShipMethod';

const AddShipZone = () => {
    const [zoneName, setZoneName] = useState('');
    const handleZoneNameChange = useCallback((newValue) => setZoneName(newValue), []);

    const [openModel, setOpenModel] = useState(false);

    const [selectedTags, setSelectedTags] = useState([]);
    const [value, setValue] = useState('');
    const [suggestion, setSuggestion] = useState('');
    const handleActiveOptionChange = useCallback(
        (activeOption) => {
            const activeOptionIsAction = activeOption === value;

            if (!activeOptionIsAction && !selectedTags.includes(activeOption)) {
                setSuggestion(activeOption);
            } else {
                setSuggestion('');
            }
        },
        [value, selectedTags],
    );
    const updateSelection = useCallback(
        (selected) => {
            const nextSelectedTags = new Set([...selectedTags]);

            if (nextSelectedTags.has(selected)) {
                nextSelectedTags.delete(selected);
            } else {
                nextSelectedTags.add(selected);
            }
            setSelectedTags([...nextSelectedTags]);
            setValue('');
            setSuggestion('');
        },
        [selectedTags],
    );
    const removeTag = useCallback(
        (tag) => () => {
            updateSelection(tag);
        },
        [updateSelection],
    );

    const getAllTags = useCallback(() => {
        const savedTags = ['Rustic', 'Antique', 'Vinyl', 'Vintage', 'Refurbished'];
        return [...new Set([...savedTags, ...selectedTags].sort())];
    }, [selectedTags]);

    const formatOptionText = useCallback(
        (option) => {
            const trimValue = value.trim().toLocaleLowerCase();
            const matchIndex = option.toLocaleLowerCase().indexOf(trimValue);

            if (!value || matchIndex === -1) return option;

            const start = option.slice(0, matchIndex);
            const highlight = option.slice(matchIndex, matchIndex + trimValue.length);
            const end = option.slice(matchIndex + trimValue.length, option.length);

            return (
                <p>
                    {start}
                    <Text variant="bodyMd" fontWeight="bold" as="span">
                        {highlight}
                    </Text>
                    {end}
                </p>
            );
        },
        [value],
    );

    const options = useMemo(() => {
        let list;
        const allTags = getAllTags();
        const filterRegex = new RegExp(value, 'i');

        if (value) {
            list = allTags.filter((tag) => tag.match(filterRegex));
        } else {
            list = allTags;
        }

        return [...list];
    }, [value, getAllTags]);

    const verticalContentMarkup =
        selectedTags.length > 0 ? (
            <Stack spacing="extraTight" alignment="center">
                {selectedTags.map((tag) => (
                    <Tag key={`option-${tag}`} onRemove={removeTag(tag)}>
                        {tag}
                    </Tag>
                ))}
            </Stack>
        ) : null;

    const optionMarkup =
        options.length > 0
            ? options.map((option) => {
                return (
                    <Listbox.Option
                        key={option}
                        value={option}
                        selected={selectedTags.includes(option)}
                        accessibilityLabel={option}
                    >
                        <Listbox.TextOption selected={selectedTags.includes(option)}>
                            {formatOptionText(option)}
                        </Listbox.TextOption>
                    </Listbox.Option>
                );
            })
            : null;

    const noResults = value && !getAllTags().includes(value);

    const actionMarkup = noResults ? (
        <Listbox.Action value={value}>{`Add "${value}"`}</Listbox.Action>
    ) : null;

    const emptyStateMarkup = optionMarkup ? null : (
        <EmptySearchResult
            title=""
            description={`No tags found matching "${value}"`}
        />
    );

    const listboxMarkup =
        optionMarkup || actionMarkup || emptyStateMarkup ? (
            <Listbox
                autoSelection="FIRST"
                onSelect={updateSelection}
                onActiveOptionChange={handleActiveOptionChange}
            >
                {actionMarkup}
                {optionMarkup}
            </Listbox>
        ) : null;
    console.log("combobox", value, suggestion, options)
    function openShipMethod () {
        setOpenModel(true);
    }
    const [rowsData, setRowsData] = useState([['', '', '']]);
    return (
        <>
            <div>
                {/* <Stack spacing="loose" distribution="fillEvenly"> */}
                <FormLayout>
                    <FormLayout.Group>
                        <label>Zone Name</label>
                        <TextField
                            // label="Zone Name"
                            value={zoneName}
                            onChange={handleZoneNameChange}
                            autoComplete="off"
                            placeholder="Zone Name"
                        />
                    </FormLayout.Group>
                    <FormLayout.Group>
                        {/* <div style={{ height: '225px' }}> */}
                        <label>Zone Regions</label>
                        <Combobox
                            allowMultiple
                            activator={
                                <Combobox.TextField
                                    autoComplete="off"
                                    label="Search Zones"
                                    labelHidden
                                    value={value}
                                    suggestion={suggestion}
                                    placeholder={(!value) ? "Search Zones" : ""}
                                    verticalContent={verticalContentMarkup}
                                    onChange={setValue}
                                />
                            }
                        >
                            {listboxMarkup}
                        </Combobox>
                        {/* </div> */}
                    </FormLayout.Group>
                    <FormLayout.Group>
                        <label>Shipping Method</label>
                        <LegacyCard>
                            <DataTable
                                showTotalsInFooter
                                columnContentTypes={[
                                    'text',
                                    'text',
                                    'text',
                                ]}
                                headings={[
                                    'Shipping Method Title',
                                    'Enabled',
                                    'Description',
                                ]}
                                rows={rowsData}
                                totals={['', '', '']}
                                totalsName={{
                                    singular: <Button outline onClick={() => openShipMethod()}>Add Shipping Method</Button>
                                }}
                            />
                        </LegacyCard>
                    </FormLayout.Group>
                </FormLayout>

            </div>
            <AddShipMethod activeModal={openModel}  />
        </>
    )
}

export default AddShipZone
