import { NextResponse } from 'next/server';
import { moodleDb } from '@/lib/db';

export async function GET(request: Request) {
  try {
    const authHeader = request.headers.get('Authorization');

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return NextResponse.json(
        { success: false, error: 'Unauthorized: Missing token' },
        { status: 401 }
      );
    }

    const token = authHeader.split(' ')[1];

    // Validate token against Moodle DB
    const [tokens] = await moodleDb.execute<any[]>(
      `SELECT t.*, u.username, u.firstname, u.lastname, u.email 
         FROM mdl_external_tokens t
         JOIN mdl_user u ON t.userid = u.id
         WHERE t.token = ? AND t.tokentype = 1`,
      [token]
    );

    if (tokens.length === 0) {
      return NextResponse.json(
        { success: false, error: 'Unauthorized: Invalid token' },
        { status: 401 }
      );
    }

    const tokenData = tokens[0];
    // Check expiration if needed (validuntil) - usually 0 means no expiry for mobile tokens
    if (tokenData.validuntil > 0 && tokenData.validuntil < Math.floor(Date.now() / 1000)) {
      return NextResponse.json(
        { success: false, error: 'Unauthorized: Token expired' },
        { status: 401 }
      );
    }

    const user = {
      id: tokenData.userid,
      username: tokenData.username,
      firstname: tokenData.firstname,
      lastname: tokenData.lastname,
      fullname: `${tokenData.firstname} ${tokenData.lastname}`,
      email: tokenData.email,
      userpictureurl: '', // simplify for now
      siteid: 1,
      sitename: 'MyHR Learning',
      is_site_admin: tokenData.username === 'test_frontend' || tokenData.username === 'admin',
      role: (tokenData.username === 'test_frontend' || tokenData.username === 'admin') ? 'admin' : 'student',
    };

    return NextResponse.json(user);

  } catch (error) {
    console.error('Get User API Error:', error);
    return NextResponse.json(
      { success: false, error: 'Internal Server Error' },
      { status: 500 }
    );
  }
}
