{% extends "manage/wrapper.tpl" %}

{% block heading %}{% trans "Posting Rates" %}{% endblock %}

{% block managecontent %}
  <form  method="post">
        <select name="time">
          <option {% if _post.time is not defined or _post.time == 24 %}selected=selected {% endif %}value="24">Today (24h)</option>
          <option {% if _post.time == 168 %}selected=selected {% endif %}value="168">Past 7 days</option>
          <option {% if _post.time == 720 %}selected=selected {% endif %}value="720">Past 30 days</option>
          <option {% if _post.time == 8760 %}selected=selected {% endif %}value="8760">Past Year</option>
          <option {% if _post.time is defined and _post.time == 0 %}selected=selected {% endif %}value="0">All time</option>
        </select>
        <input type="submit" name="Submit">
  </form>
<table>
  <tr>
    <th>Board</th>
    <th>Posts</th>
    <th>Uniques</th>
    <th>Files</th>
  </tr>
{% for board,stat in stats %}
  <tr>
    <td>
      {{ board }}
    </td>
    <td>
      {{ stat.posts }}
    </td>
    <td>
      {{ stat.uniques }}
    </td>
    <td>
      {{ stat.files }}
    </td>
  </tr>
{% endfor %}
</table>
{% endblock %}