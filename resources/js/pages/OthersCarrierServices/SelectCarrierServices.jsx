import React, { useState, useEffect, useCallback } from 'react'
import AddEasyPost from './easypost/AddEasyPost'
import AddShippo from './Shippo/AddShippo'
import {
  Page,
  Card,
  Select,
  Grid
} from '@shopify/polaris';
import { useParams } from 'react-router-dom';

export default function SelectCarrierServices() {
  const { id, carrier_type } = useParams();
  const [selected, setSelected] = useState('easypost');

  const handleSelectChange = useCallback(
    (value) => setSelected(value),
    [],
  );

  const options = [
    { label: 'EasyPost', value: 'easypost' },
    { label: 'Shippo', value: 'shippo' },
  ];

  useEffect(() => {
    if (carrier_type && id) {
      setSelected(carrier_type)
    }
  }, [carrier_type, id])
  return (
    <div className='AddCarrierServicesPage'>
      <Page
        // fullWidth
        title=" Add Carrier Services"
        backAction={{ content: 'Products', onAction: () => window.history.back() }}
      >
        <div className='services-page-opt-select'>
          <Card className='AddCarrierServicesPage-card'>
            <Grid>
              <Grid.Cell columnSpan={{ xs: 12, sm: 12, md: 12, lg: 12, xl: 12 }}>
                <Select
                  label="Select Carrier Services"
                  options={options}
                  onChange={handleSelectChange}
                  value={selected}
                />
              </Grid.Cell>
            </Grid>

          </Card>
        </div>
        <div>
          {selected == "easypost" && <AddEasyPost selected={selected} setSelected={setSelected} id={id} />}
          {selected == "shippo" && <AddShippo selected={selected} setSelected={setSelected} id={id} />}
        </div>

      </Page>
    </div>
  )
}
