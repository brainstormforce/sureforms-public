import { InstantFormToggle } from "../InstantForm"

const ConversationalFormSettings = () => {
  return (
    <div 
    style={{
        'display': 'flex',
        'flexDirection': 'column',
        'gap': '8px',
    }}>
        <div
        style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
        }}
        >
            <span>Conversational Layout</span>
            <span
            style={{
                'minWidth': '16px',
                'maxHeight': '16px',
                'padding': '2px',
                'gap': '2px',
                'borderRadius': '4px',
                'border': '0.5px',
                'background': '#1F2937',
                'color': '#fff',
                'border': '0.5px solid #374151',
                'fontSize': '9px',
                'fontWeight': '600',
                'lineHeight': '1',
                'textAlign': 'center',
            }}
            >Business Plan</span>
            <InstantFormToggle 
            disabled={true}    
            />
        </div>
        <div
        style={{
            'padding': '4px',
            'borderRadius': '6px',
            'border': '0.5px',
            'background': '#F0F9FF',
            'color': '#1E293B',
            'border': '0.5px solid #BAE6FD'
        }}
        >
            To use this feature you need to upgrade to the Business plan. Upgrade Now
        </div>
    </div>
  )
}

export default ConversationalFormSettings