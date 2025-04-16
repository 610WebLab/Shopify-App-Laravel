import React from 'react'
import { Page } from '@shopify/polaris';
export default function EasyPostTabs() {
  return (
    <Page
    backAction={{
        content: 'Products',
        onAction: () => window.history.back(),
      }}
    title="Carrier Details"
      >
          
  </Page>
  )
}
