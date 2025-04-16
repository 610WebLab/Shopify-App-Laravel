import React, { useEffect, useState, useCallback } from 'react'
import {
  TextField,
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
  Pagination
} from '@shopify/polaris';

const OtherOrder = () => {
  const [orders, setOrders] = useState([]);
  const [loader, setLoader] = useState(true);
  const [current, setCurrent] = useState(null);
  const { mode, setMode } = useSetIndexFiltersMode(IndexFiltersMode.Filtering);
  const [queryValue, setQueryValue] = useState(undefined);
  const handleQueryValueChange = useCallback((value) => setQueryValue(value), []);
  const handleQueryValueRemove = useCallback(() => setQueryValue(''), []);
  const handleFiltersClearAll = useCallback(() => { handleQueryValueRemove(); }, [handleQueryValueRemove]);
  /* start pagination  */
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
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
  /* end  pagination  */
  const tabs = [];
  const filters = [];
  // const orders = [
  //   {
  //     id: '1020',
  //     order: '#1020',
  //     date: 'Jul 20 at 4:34pm',
  //     customer: 'Jaydon Stanton',
  //     total: '$969.44',
  //     paymentStatus: <Badge progress="complete">Paid</Badge>,
  //     fulfillmentStatus: <Badge progress="incomplete">Unfulfilled</Badge>,
  //   },
  //   {
  //     id: '1019',
  //     order: '#1019',
  //     date: 'Jul 20 at 3:46pm',
  //     customer: 'Ruben Westerfelt',
  //     total: '$701.19',
  //     paymentStatus: <Badge progress="partiallyComplete">Partially paid</Badge>,
  //     fulfillmentStatus: <Badge progress="incomplete">Unfulfilled</Badge>,
  //   },
  //   {
  //     id: '1018',
  //     order: '#1018',
  //     date: 'Jul 20 at 3.44pm',
  //     customer: 'Leo Carder',
  //     total: '$798.24',
  //     paymentStatus: <Badge progress="complete">Paid</Badge>,
  //     fulfillmentStatus: <Badge progress="incomplete">Unfulfilled</Badge>,
  //   },
  // ];
  useEffect(() => {
    getOrderData(page, queryValue);
  }, [page, queryValue]);

  const getOrderData = (page, queryValue) => {
    setLoader(false);
    fetch("/orders?shop=" + Config.shop + "&page=" + page + "&search=" + queryValue + "&type=Canceled")
      .then((res) => res.json())
      .then(
        (result) => {
          setLoader(true);
          if (result.status) {
            setOrders(result.data.data)
            setPage(result.data.current_page);
            setLastPage(result.data.last_page);
          }
        },
        (error) => {
          setLoader(false);
        }
      );
  }
  const formatDate = (inputDate) => {
    const options = {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric',
      hour12: true,
    };
    const formattedDate = new Intl.DateTimeFormat('en-US', options).format(new Date(inputDate));
    return formattedDate;
  };
  const resourceName = {
    singular: 'order',
    plural: 'orders',
  };

  const statusConfig = {
    paid: "success",
    pending: "attention",
    refunded: "info"
  };

  const fulfillmentConfig = {
    Fulfilled: { progress: "complete", tone: "" },
    Unfulfilled: { progress: "incomplete", tone: "attention" },
    Canceled: { progress: "complete", tone: "critical" }
  };
  const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(orders);
  const rowMarkup = orders.map(
    (
      { id, customer_email, customer_id, customer_name, date, delivery_method, delivery_status, fullfilement, item_count, labels, order_id, order_name, order_no, payment_status, tags, total, user_id, created_at }, index,) => (
      <IndexTable.Row
        id={id}
        key={id}
        selected={selectedResources.includes(id)}
        position={index}
      >
        <IndexTable.Cell> <Text variant="bodyMd" fontWeight="bold" as="span">{order_name}</Text></IndexTable.Cell>
        <IndexTable.Cell>{formatDate(created_at)}</IndexTable.Cell>
        <IndexTable.Cell>
          <Badge status={statusConfig[payment_status]}>
            {payment_status.charAt(0).toUpperCase() + payment_status.slice(1)}
          </Badge>
        </IndexTable.Cell>
        <IndexTable.Cell>
          <Badge progress={fulfillmentConfig[fullfilement]?.progress} status={fulfillmentConfig[fullfilement]?.tone}>
            {fullfilement}
          </Badge>
        </IndexTable.Cell>
        <IndexTable.Cell>{delivery_status}</IndexTable.Cell>
        <IndexTable.Cell>{customer_name}</IndexTable.Cell>
        <IndexTable.Cell>{item_count > 1
          ?
          `${item_count} Items`
          :
          `${item_count} Item`}</IndexTable.Cell>
        <IndexTable.Cell>{delivery_method}</IndexTable.Cell>
        <IndexTable.Cell>{total}</IndexTable.Cell>
        <IndexTable.Cell>{tags}</IndexTable.Cell>

      </IndexTable.Row>
    ),
  );

  return (
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
        itemCount={orders.length}
        selectedItemsCount={
          allResourcesSelected ? 'All' : selectedResources.length
        }
        onSelectionChange={handleSelectionChange}
        headings={[
          { title: "Order" },
          { title: "Date" },
          // { title: "Ecom Order ID" },
          { title: "Payment status" },
          { title: "Fulfillment status" },
          { title: "Delivery status" },
          { title: "Customer" },
          { title: "Items" },
          { title: "Delivery Service" },
          // { title: 'Packages' },
          // { title: 'Box' },
          // { title: 'Package Weight (LB)' },
          { title: "Total" },
          { title: "Delivery Price" },
        ]}
        // pagination={{
        //   hasPrevious: true,
        //   onPrevious: () => {
        //     prevPage();
        //   },
        //   hasNext: true,
        //   onNext: () => {
        //     nextPage();
        //   },
        // }}
        loading={!loader}
      >
        {loader && rowMarkup}
      </IndexTable>
      {orders.length > 0 && <Pagination
          hasPrevious
          onPrevious={() => {
            console.log('Previous');
            prevPage();
          }}
          hasNext
          onNext={() => {
            console.log('Next');
            nextPage();
          }}
        />}
    </LegacyCard>
  );
}

export default OtherOrder
