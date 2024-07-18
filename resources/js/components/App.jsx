import React from 'react'
import AppFrame from '../routing/AppFrame';
import { Page, Layout, Card, Frame } from '@shopify/polaris';

const App = () => {
  return (
    <Frame>
      <Page fullWidth>
        <AppFrame />
      </Page>
    </Frame>
  )
}

export default App


