import React, {Fragment} from 'react'
import isEmpty from 'lodash/isEmpty'

import {trans} from '#/main/app/intl/translation'
import {PropTypes as T, implementPropTypes} from '#/main/app/prop-types'
import {FormField as FormFieldTypes} from '#/main/core/layout/form/prop-types'
import {EmptyPlaceholder} from '#/main/core/layout/components/placeholder'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'

import {ResourceCard} from '#/main/core/resource/components/card'
import {ResourceNode as ResourceNodeTypes} from '#/main/core/resource/prop-types'
import {MODAL_RESOURCE_EXPLORER} from '#/main/core/modals/resources'

const ResourcesButton = props =>
  <Button
    type={MODAL_BUTTON}
    className="btn"
    icon="fa fa-fw fa-folder"
    label={trans('add_resources')}
    primary={true}
    modal={[MODAL_RESOURCE_EXPLORER, {
      title: props.title,
      current: props.current,
      root: props.root,
      selectAction: (selected) => ({
        type: CALLBACK_BUTTON,
        label: trans('select', {}, 'actions'),
        callback: () => props.onChange(selected)
      })
    }]}
    style={{
      marginTop: '10px' // todo
    }}
  />

ResourcesButton.propTypes = {
  title: T.string,
  current: T.shape(ResourceNodeTypes.propTypes),
  root: T.shape(ResourceNodeTypes.propTypes),
  onChange: T.func.isRequired
}

const ResourcesInput = props => {
  if (!isEmpty(props.value)) {
    return(
      <Fragment>
        {props.value.map(resource =>
          <ResourceCard
            key={`resource-card-${resource.id}`}
            data={resource}
            actions={[
              {
                name: 'delete',
                type: CALLBACK_BUTTON,
                icon: 'fa fa-fw fa-trash-o',
                label: trans('delete', {}, 'actions'),
                dangerous: true,
                callback: () => {
                  const newValue = props.value
                  const index = newValue.findIndex(r => r.id === resource.id)

                  if (-1 < index) {
                    newValue.splice(index, 1)
                    props.onChange(newValue)
                  }
                }
              }
            ]}
          />
        )}

        <ResourcesButton
          {...props.picker}
          onChange={(selected) => {
            const newValue = props.value
            selected.forEach(resource => {
              const index = newValue.findIndex(r => r.id === resource.id)

              if (-1 === index) {
                newValue.push(resource)
              }
            })
            props.onChange(newValue)
          }}
        />
      </Fragment>
    )
  }

  return(
    <EmptyPlaceholder
      id={props.id}
      size="lg"
      icon="fa fa-folder"
      title={trans('no_resource')}
    >
      <ResourcesButton
        {...props.picker}
        onChange={props.onChange}
      />
    </EmptyPlaceholder>
  )
}


implementPropTypes(ResourcesInput, FormFieldTypes, {
  value: T.arrayOf(T.shape(
    ResourceNodeTypes.propTypes
  )),
  picker: T.shape({
    title: T.string,
    current: T.shape(ResourceNodeTypes.propTypes),
    root: T.shape(ResourceNodeTypes.propTypes)
  })
}, {
  value: null,
  picker: {
    title: trans('resource_picker'),
    current: null,
    root: null
  }
})

export {
  ResourcesInput
}