import React from 'react'
import {PropTypes as T} from 'prop-types'
import {connect} from 'react-redux'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {selectors as formSelect} from '#/main/app/content/form/store'
import {FormData} from '#/main/app/content/form/containers/data'

import {constants} from '#/plugin/portfolio/tools/portfolio/constants'
import {Portfolio as PortfolioType} from '#/plugin/portfolio/tools/portfolio/prop-types'

const PortfolioFormComponent = (props) =>
  <FormData
    level={2}
    name="portfolio"
    target={(portfolio, isNew) => isNew ?
      ['apiv2_portfolio_create'] :
      ['apiv2_portfolio_update', {id: portfolio.id}]
    }
    buttons={true}
    cancel={{
      type: LINK_BUTTON,
      target: props.isNew ? '/' : `/portfolios/${props.portfolio.id}`,
      exact: true
    }}
    sections={[
      {
        title: trans('general'),
        primary: true,
        fields: [
          {
            name: 'title',
            type: 'string',
            label: trans('title'),
            required: true
          }, {
            name: 'meta.visibility',
            type: 'choice',
            label: trans('visibility', {}, 'portfolio'),
            required: true,
            options: {
              multiple: false,
              condensed: true,
              choices: constants.VISIBILITIES
            }
          }
        ]
      }
    ]}
  />

PortfolioFormComponent.propTypes = {
  portfolio: T.shape(PortfolioType.propTypes),
  isNew: T.bool
}

const PortfolioForm = connect(
  (state) => ({
    portfolio: formSelect.data(formSelect.form(state, 'portfolio')),
    isNew: formSelect.isNew(formSelect.form(state, 'portfolio'))
  })
)(PortfolioFormComponent)

export {
  PortfolioForm
}
