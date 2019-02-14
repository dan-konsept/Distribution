import React from 'react'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ListData} from '#/main/app/content/list/containers/data'
import {DataCard} from '#/main/app/content/card/components/data'

import {constants} from '#/plugin/portfolio/tools/portfolio/constants'

const Portfolios = () =>
  <ListData
    name="portfolios"
    fetch={{
      url: ['apiv2_portfolio_my_list'],
      autoload: true
    }}
    primaryAction={(row) => ({
      type: LINK_BUTTON,
      target: `/portfolios/${row.id}`
    })}
    actions={(rows) => [
      {
        type: LINK_BUTTON,
        icon: 'fa fa-fw fa-pencil',
        label: trans('edit'),
        scope: ['object'],
        target: '/portfolio/' + rows[0].id
      }
    ]}
    delete={{
      url: ['apiv2_portfolio_delete_bulk']
    }}
    definition={[
      {
        name: 'title',
        label: trans('title'),
        type: 'string',
        primary: true,
        displayed: true
      }, {
        name: 'meta.slug',
        label: trans('slug'),
        type: 'string',
        displayed: true
      }, {
        name: 'meta.visibility',
        label: trans('visibility', {}, 'portfolio'),
        type: 'string',
        displayed: true,
        filterable: false,
        calculated: (rowData) => trans(rowData.meta.visibility, {}, 'portfolio')
      }, {
        name: 'visibility',
        label: trans('visibility', {}, 'portfolio'),
        type: 'choice',
        displayed: false,
        displayable: false,
        sortable: false,
        filterable: true,
        options: {
          choices: constants.VISIBILITIES
        }
      }
    ]}

    card={(row) =>
      <DataCard
        icon='fa fa-list-alt'
        title={row.data.title}
        subtitle={row.data.meta.slug}
        contentText={trans(row.meta.visibility, {}, 'portfolio')}
      />
    }
  />

export {
  Portfolios
}
