from flask import Flask, request, jsonify
import os

app = Flask(__name__)

GOVT_RULES = """
STRICT DRAFTING STRUCTURE & RULES:
1. MANDATORY HEADER: Every letter MUST start with:
   - File No: [Placeholder]
   - Letter No: [Placeholder]
   - Date: [Current Date]

2. ADDRESSEE: Place below the header.

3. SUBJECT: A clear 'Subject:' line is mandatory.

4. SALUTATION:
   - OFFICIAL (Higher Authority/Dept): NO salutation.
   - PUBLIC/AGENCY: Use 'Sir/Madam,'.

5. SIGNATURE BLOCKS:
   - SIGNATURE BLOCK I: At end of body if addressee exists.
   - COPY TO: If CC exists.
   - SIGNATURE BLOCK II: Below CC.
"""

@app.route('/orchestrate-prompt', methods=['POST'])
def orchestrate_prompt():
    try:
        data = request.get_json()
        recipient = data.get('recipient')
        agreement = data.get('agreement')
        sanction = data.get('sanction')
        agency = data.get('agency')
        letter_type = data.get('type', 'General')
        context = data.get('context', '')
        office_name = data.get('office_name', 'DDA Sports Complex')
        
        is_official = recipient and recipient.get('category') in ['Higher Authority', 'Internal Department']

        prompt = f"You are an expert Executive Assistant at DDA. {GOVT_RULES}\n\n"
        
        if recipient:
            prompt += f"RECIPIENT:\nName: {recipient.get('name')}\nDesignation: {recipient.get('designation')}\nDept: {recipient.get('department')}\nAddr: {recipient.get('address')}\n\n"
        
        if agreement:
            prompt += f"POST-AWARD CONTEXT:\nAgmt No: {agreement.get('agreement_no')}\nAgency: {agency.get('name')}\n\n"
        elif sanction:
            prompt += f"PRE-AWARD CONTEXT:\nSanction (AA&ES) for: {sanction.get('sub_head')}\nSanctioned Amount: Rs. {sanction.get('amount')}\n\n"

        # Specific Logic for Award/PG Letters
        if letter_type == 'PG_Request':
            prompt += "INTENT: Request the agency to submit a Performance Guarantee (PG) of 3% of the tendered amount within 15 days. "
        elif letter_type == 'Award_Letter':
            prompt += "INTENT: Formally award the contract to the agency based on the approved AA&ES. Ask them to sign the agreement. "
        elif letter_type == 'Request_Letter':
            prompt += "INTENT: A formal request to the agency regarding operational matters of the running agreement. "

        prompt += f"USER INSTRUCTIONS: {context}\n\n"
        prompt += f"SENDER: {office_name}\n\n"
        prompt += "Draft the complete letter now."

        return jsonify({'orchestrated_prompt': prompt})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=False)
