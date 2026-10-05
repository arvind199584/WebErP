import json
import os

def generate_sql_from_intent(intent: str, entities: dict, intent_map_path: str) -> dict:
    """
    Generates a SQL query and parameters based on the intent and extracted entities.
    """
    if not os.path.exists(intent_map_path):
        return None

    with open(intent_map_path, 'r') as f:
        intent_map = json.load(f)

    if intent not in intent_map:
        return None

    config = intent_map[intent]

    if config.get('action_type') == 'INSERT':
        target_table = config.get('target_table')
        entity_map = config.get('entity_map', {})
        data = {}
        for entity_key, col_name in entity_map.items():
            if entity_key in entities:
                data[col_name] = entities[entity_key]
        return {
            'action_type': 'INSERT',
            'target_table': target_table,
            'data': data
        }

    sql = config.get('sql')
    if not sql:
        return None

    params = {}

    if 'filters' in config:
        for entity_key, filter_sql in config['filters'].items():
            if entity_key in entities:
                sql += " " + filter_sql
                if entity_key == 'date':
                    params['date_param'] = entities['date']
                else:
                    params[entity_key] = f"%{entities[entity_key]}%"

    if 'group_by' in config:
        sql += config['group_by']

    # Dynamically add ORDER BY clause if a sort direction is found
    if 'sort_direction' in entities:
        # This is a simple assumption; a more complex system might map intents to sortable columns
        sort_column = 'log_date'
        if 'receipt' in intent:
            sort_column = 'transaction_date'

        sql += f" ORDER BY {sort_column} {entities['sort_direction']}"

    chart_config = config.get('chart_config')

    return {'sql': sql, 'params': params, 'chart_config': chart_config}
